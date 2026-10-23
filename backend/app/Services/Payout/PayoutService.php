<?php

namespace App\Services\Payout;

use App\DTOs\Payout\PayoutRequestData;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Provider\ProviderStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\LedgerAccount;
use App\Models\PayoutRequest;
use App\Models\ProviderPayoutMethod;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Repositories\Contracts\Payout\ProviderPayoutMethodRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Finance\LedgerAccountResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * The provider side of payouts (SRS §12.3, Plan P3.4).
 *
 * request(): validates the amount against the min / max settings and the verified method, then, in ONE transaction that first
 * locks the provider's wallet row, checks the wallet balance and reserves the amount with a ledger entry (wallet -> payout
 * clearing). The wallet lock serialises concurrent requests of the same provider, so two requests can never reserve the same
 * money; the Idempotency-Key makes a replay return the first request.
 * cancel(): the provider withdraws a request nobody approved yet; the reservation is reversed.
 */
final class PayoutService
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly ProviderPayoutMethodRepositoryInterface $methods,
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly LedgerAccountResolver $resolver,
        private readonly PayoutPostingService $postings,
        private readonly PayoutQuoteService $quotes,
        private readonly ProviderBalanceService $balances,
        private readonly PayoutHistory $history,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation the provider, method, amount or balance does not allow the payout
     */
    public function request(ProviderProfile $provider, PayoutRequestData $data): PayoutRequest
    {
        $existing = $this->payouts->findByIdempotencyKey($provider->id, $data->idempotencyKey);

        if ($existing instanceof PayoutRequest) {
            return $this->replay($existing, $data);
        }

        if ($provider->status !== ProviderStatus::Approved) {
            throw BusinessRuleViolation::make('payout_provider_not_approved');
        }

        $method = $this->methods->findForProvider($provider->id, $data->methodUuid);

        if (! $method instanceof ProviderPayoutMethod || ! $method->isVerified()) {
            throw BusinessRuleViolation::make('payout_method_not_verified', 'payout_method_id');
        }

        $currency = $this->balances->currency();
        $this->assertAmount($data->amountMinor);

        try {
            return $this->db->transaction(fn (): PayoutRequest => $this->requestLocked($provider, $method, $data, $currency));
        } catch (UniqueConstraintViolationException) {
            $winner = $this->payouts->findByIdempotencyKey($provider->id, $data->idempotencyKey);

            return $winner instanceof PayoutRequest ? $this->replay($winner, $data) : throw BusinessRuleViolation::make('idempotency_key_reused');
        }
    }

    public function cancel(ProviderProfile $provider, string $uuid): PayoutRequest
    {
        $found = $this->payouts->findForProvider($provider->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(PayoutRequest::class);

        return $this->db->transaction(function () use ($provider, $found): PayoutRequest {
            $payout = $this->payouts->lockById($found->id) ?? throw (new ModelNotFoundException)->setModel(PayoutRequest::class);

            if (! $payout->status->isCancellableByProvider()) {
                throw ConflictException::make('payout_not_cancellable');
            }

            $release = $this->postings->release($payout, 'payout_cancelled_by_provider', null);
            $from = $payout->status;
            $this->payouts->update($payout, ['status' => PayoutStatus::Cancelled, 'release_entry_id' => $release->id, 'closed_at' => CarbonImmutable::now()]);
            $this->history->record($payout, $from, PayoutStatus::Cancelled, $provider->user, 'cancelled_by_provider');
            $this->audit->record(AuditAction::StatusChanged, $payout, $provider->user, before: ['status' => $from->value], after: ['status' => PayoutStatus::Cancelled->value]);

            return $payout;
        });
    }

    /**
     * @return LengthAwarePaginator<int, PayoutRequest>
     */
    public function page(ProviderProfile $provider, int $perPage): LengthAwarePaginator
    {
        return $this->payouts->pageForProvider($provider->id, max(1, min(50, $perPage)));
    }

    public function find(ProviderProfile $provider, string $uuid): PayoutRequest
    {
        return $this->payouts->findForProvider($provider->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(PayoutRequest::class);
    }

    private function requestLocked(ProviderProfile $provider, ProviderPayoutMethod $method, PayoutRequestData $data, string $currency): PayoutRequest
    {
        $wallet = $this->resolver->providerWallet($provider, $currency);
        // The serialisation point: every payout request and every posting to this wallet waits here.
        $locked = $this->accounts->lockInOrder([$wallet->id])->get($wallet->id) ?? throw new ModelNotFoundException;

        $again = $this->payouts->findByIdempotencyKey($provider->id, $data->idempotencyKey);

        if ($again instanceof PayoutRequest) {
            return $this->replay($again, $data);
        }

        if ($locked->balance_minor < $data->amountMinor) {
            throw BusinessRuleViolation::make('payout_insufficient_balance', 'amount_minor', ['max' => max(0, $locked->balance_minor)]);
        }

        $quote = $this->quotes->quote($data->amountMinor, $currency);

        $payout = $this->payouts->insert([
            'provider_profile_id' => $provider->id,
            'payout_method_id' => $method->id,
            'method_snapshot' => $method->masked(),
            'amount_minor' => $quote->amountMinor,
            'fee_minor' => $quote->feeMinor,
            'net_minor' => $quote->netMinor,
            'currency' => $currency,
            'status' => PayoutStatus::Requested,
            'requires_dual_approval' => $quote->requiresDualApproval,
            'idempotency_key' => $data->idempotencyKey,
        ]);

        $entry = $this->postings->reserve($payout, $this->wallet($locked));
        $this->payouts->update($payout, ['reserve_entry_id' => $entry->id]);
        $this->history->record($payout, null, PayoutStatus::Requested, $provider->user, meta: ['amount_minor' => $quote->amountMinor, 'fee_minor' => $quote->feeMinor]);
        $this->audit->record(AuditAction::Requested, $payout, $provider->user, after: ['amount_minor' => $quote->amountMinor, 'fee_minor' => $quote->feeMinor, 'dual_approval' => $quote->requiresDualApproval]);

        return $payout;
    }

    private function wallet(LedgerAccount $locked): LedgerAccount
    {
        if ($locked->type !== LedgerAccountType::ProviderWallet) {
            throw new LogicException('A payout reserves a provider wallet.');
        }

        return $locked;
    }

    private function assertAmount(int $amountMinor): void
    {
        $min = $this->quotes->minMinor();
        $max = $this->quotes->maxMinor();

        if ($amountMinor < $min) {
            throw BusinessRuleViolation::make('payout_amount_below_minimum', 'amount_minor', ['min' => $min, 'max' => $max]);
        }

        if ($amountMinor > $max) {
            throw BusinessRuleViolation::make('payout_amount_above_maximum', 'amount_minor', ['min' => $min, 'max' => $max]);
        }
    }

    private function replay(PayoutRequest $existing, PayoutRequestData $data): PayoutRequest
    {
        $method = $this->methods->find($existing->payout_method_id ?? 0);

        if ($existing->amount_minor !== $data->amountMinor || $method?->uuid !== $data->methodUuid) {
            throw BusinessRuleViolation::make('idempotency_key_reused');
        }

        return $existing;
    }
}
