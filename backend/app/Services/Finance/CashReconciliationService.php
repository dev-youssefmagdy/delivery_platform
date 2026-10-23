<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CashFigures;
use App\DTOs\Finance\CashReconciliationOpenData;
use App\DTOs\Finance\CashSettlementData;
use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\CashReconciliationStatus;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Notification\NotificationEvent;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\CashReconciliation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Finance\CashReconciliationRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Finance\ProviderEarningsRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\PartyNotifier;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Cash reconciliation (SRS §7.6: cash jobs must reconcile the commission owed). The provider's wallet is already net: a cash job
 * debits the commission it owes (Plan §7.4) and any later earnings credit offset it automatically, so there is no separate
 * "deduction" posting. What reconciliation adds is the SETTLEMENT of the remaining debt: an admin records the money the provider
 * handed over (cash deposit, transfer, wallet), which is posted as gateway clearing -> provider wallet.
 *
 * open(): snapshot of the period's cash jobs (cash collected, commission owed) from the stored price breakdowns.
 * settle(): under the wallet lock, the amount must be positive and at most the debt the wallet shows right now.
 */
final class CashReconciliationService
{
    private const MAX_PERIOD_DAYS = 366;

    public function __construct(
        private readonly CashReconciliationRepositoryInterface $reconciliations,
        private readonly ProviderEarningsRepositoryInterface $earnings,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly LedgerAccountResolver $resolver,
        private readonly LedgerService $ledger,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly PartyNotifier $notifier,
    ) {}

    /**
     * Cash figures of a provider for a period of market-timezone dates (inclusive).
     */
    public function figures(ProviderProfile $provider, CarbonImmutable $from, CarbonImmutable $to, string $currency): CashFigures
    {
        $zone = (string) config('admin.display_timezone', 'UTC');
        $jobs = $this->earnings->settledBetween($provider->id, $from->setTimezone($zone)->startOfDay(), $to->setTimezone($zone)->addDay()->startOfDay(), $currency)
            ->filter(static fn (Booking $booking): bool => $booking->payment_method === PaymentMethodType::Cash);

        $collected = 0;
        $owed = 0;

        foreach ($jobs as $booking) {
            $breakdown = (array) $booking->price_breakdown;
            $payable = (int) ($breakdown['customer_payable_minor'] ?? 0);
            $collected += $payable;
            $owed += $payable - (int) ($breakdown['provider_earning_minor'] ?? 0);
        }

        return new CashFigures($jobs->count(), $collected, max(0, $owed));
    }

    public function open(Admin $actor, CashReconciliationOpenData $data): CashReconciliation
    {
        $existing = $this->reconciliations->findByIdempotencyKey($data->idempotencyKey);

        if ($existing instanceof CashReconciliation) {
            return $existing;
        }

        $provider = $this->providers->findManyByIds([$data->providerId])->first() ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);

        if ($data->to->lessThan($data->from) || $data->from->diffInDays($data->to) > self::MAX_PERIOD_DAYS) {
            throw BusinessRuleViolation::make('cash_period_invalid', 'period_to');
        }

        $currency = (string) $this->settings->string(SettingKey::DefaultCurrency);
        $figures = $this->figures($provider, $data->from, $data->to, $currency);

        try {
            return $this->db->transaction(function () use ($actor, $data, $currency, $figures, $provider): CashReconciliation {
                $reconciliation = $this->reconciliations->insert([
                    'provider_profile_id' => $data->providerId,
                    'currency' => $currency,
                    'period_from' => $data->from->toDateString(),
                    'period_to' => $data->to->toDateString(),
                    'cash_jobs' => $figures->jobs,
                    'cash_collected_minor' => $figures->cashCollectedMinor,
                    'commission_due_minor' => $figures->commissionDueMinor,
                    'status' => CashReconciliationStatus::Open,
                    'notes' => $data->notes,
                    'created_by_id' => $actor->id,
                    'idempotency_key' => $data->idempotencyKey,
                ]);

                $this->audit->record(AuditAction::Created, $reconciliation, $actor, after: [
                    'provider_profile_id' => $data->providerId,
                    'period' => $reconciliation->period_from->toDateString().'..'.$reconciliation->period_to->toDateString(),
                    'cash_collected_minor' => $figures->cashCollectedMinor,
                    'commission_due_minor' => $figures->commissionDueMinor,
                ], reason: $data->notes);

                // The provider is told that cash commission is due; the amount is shown in the app, not on the lock screen.
                if ($figures->commissionDueMinor > 0) {
                    $this->notifier->providerAccount($provider, NotificationEvent::CashDebtNotice, $reconciliation->uuid, linkId: $reconciliation->uuid);
                }

                return $reconciliation;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->reconciliations->findByIdempotencyKey($data->idempotencyKey) ?? throw BusinessRuleViolation::make('idempotency_key_reused');
        }
    }

    public function settle(Admin $actor, CashReconciliation $reconciliation, CashSettlementData $data): CashReconciliation
    {
        return $this->db->transaction(function () use ($actor, $reconciliation, $data): CashReconciliation {
            $locked = $this->reconciliations->lockById($reconciliation->id) ?? throw (new ModelNotFoundException)->setModel(CashReconciliation::class);

            if ($locked->status !== CashReconciliationStatus::Open) {
                throw ConflictException::make('cash_settlement_not_open');
            }

            if ($data->amountMinor <= 0) {
                throw BusinessRuleViolation::make('cash_settlement_amount_invalid', 'amount_minor');
            }

            $provider = $this->providers->findManyByIds([$locked->provider_profile_id])->first() ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
            $wallet = $this->resolver->providerWallet($provider, $locked->currency);
            // Serialises against payouts and settlements of the same wallet; the debt is read under the lock.
            $lockedWallet = $this->accounts->lockInOrder([$wallet->id])->get($wallet->id) ?? throw new ModelNotFoundException;
            $debt = max(0, -$lockedWallet->balance_minor);

            if ($data->amountMinor > $debt) {
                throw BusinessRuleViolation::make('cash_settlement_exceeds_debt', 'amount_minor', ['max' => $debt]);
            }

            $amount = Money::of($data->amountMinor, $this->currencies->get($locked->currency));
            $gateway = $this->resolver->platform(LedgerAccountType::GatewayClearing, $locked->currency);
            $entry = $this->ledger->post(new JournalEntryData(
                type: JournalEntryType::CashSettlement,
                idempotencyKey: 'cash-settlement:'.$locked->uuid,
                descriptionKey: 'ledger.entry.cash_settlement',
                lines: [JournalLineData::debit($gateway, $amount), JournalLineData::credit($lockedWallet, $amount)],
                referenceType: 'cash_reconciliation',
                referenceId: $locked->id,
                meta: ['reconciliation' => $locked->uuid, 'method' => $data->method->value, 'reference' => $data->reference, 'reason' => $data->reason],
                actorType: ActorType::Admin,
                actorId: $actor->id,
            ))->entry;

            $this->reconciliations->update($locked, [
                'status' => CashReconciliationStatus::Settled,
                'settled_minor' => $data->amountMinor,
                'method' => $data->method,
                'reference' => $data->reference,
                'journal_entry_id' => $entry->id,
                'settled_by_id' => $actor->id,
                'settled_at' => CarbonImmutable::now(),
            ]);
            $this->audit->record(AuditAction::Reconciled, $locked, $actor, after: ['settled_minor' => $data->amountMinor, 'method' => $data->method->value, 'entry' => $entry->uuid], reason: $data->reason);

            return $locked;
        });
    }

    public function cancel(Admin $actor, CashReconciliation $reconciliation, string $reason): CashReconciliation
    {
        return $this->db->transaction(function () use ($actor, $reconciliation, $reason): CashReconciliation {
            $locked = $this->reconciliations->lockById($reconciliation->id) ?? throw (new ModelNotFoundException)->setModel(CashReconciliation::class);

            if ($locked->status !== CashReconciliationStatus::Open) {
                throw ConflictException::make('cash_settlement_not_open');
            }

            $this->reconciliations->update($locked, ['status' => CashReconciliationStatus::Cancelled]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, after: ['status' => CashReconciliationStatus::Cancelled->value], reason: $reason);

            return $locked;
        });
    }
}
