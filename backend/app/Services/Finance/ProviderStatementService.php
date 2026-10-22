<?php

namespace App\Services\Finance;

use App\Enums\Common\AuditAction;
use App\Enums\Finance\LedgerAccountType;
use App\Models\Admin;
use App\Models\JournalLine;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Provider statement export (SRS §7.6): the lines of the provider's wallet in a period with the opening balance, the credits and
 * debits, the closing balance and the wallet's cached ledger balance, so the totals can be proven against the ledger:
 * opening + credits - debits = closing, and for an open-ended period closing = the account balance. Finance-permissioned
 * and audited by the caller's controller through this service.
 */
final class ProviderStatementService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly JournalLineRepositoryInterface $lines,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Statement rows as CSV cells (header first), plus the totals for the caller/tests.
     *
     * @return array{rows: list<list<string>>, totals: array{opening_minor: int, credits_minor: int, debits_minor: int, closing_minor: int, ledger_balance_minor: int}}
     */
    public function build(Admin $actor, ProviderProfile $provider, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $zone = (string) config('admin.display_timezone', 'UTC');
        $fromAt = $from?->setTimezone($zone)->startOfDay();
        $toAt = $to?->setTimezone($zone)->addDay()->startOfDay();
        $accounts = $this->accounts->forOwner(LedgerAccountType::ProviderWallet, $provider->id);

        $rows = [['posted_at', 'entry', 'type', 'description_key', 'debit', 'credit', 'change', 'balance_after', 'currency']];
        $opening = 0;
        $credits = 0;
        $debits = 0;
        $ledger = 0;
        $change = 0;

        foreach ($accounts as $account) {
            $currency = $this->currencies->get($account->currency);
            $format = fn (int $minor): string => Money::of($minor, $currency)->toDecimal();
            $opening += $fromAt === null ? 0 : $this->lines->balanceBefore($account->id, $fromAt);
            $ledger += $account->balance_minor;

            foreach ($this->lines->statementBetween([$account->id], $fromAt, $toAt) as $line) {
                $isCredit = $line->side->value === 'credit';
                // Provider wallets are credit-normal: credit adds, debit subtracts.
                $signed = $line->side === $account->type->normalSide() ? $line->amount_minor : -$line->amount_minor;
                $credits += $isCredit ? $line->amount_minor : 0;
                $debits += $isCredit ? 0 : $line->amount_minor;
                $change += $signed;
                $rows[] = [
                    $this->postedAt($line, $zone),
                    $line->entry->uuid,
                    $line->entry->type->value,
                    $line->entry->description_key,
                    $isCredit ? '' : $format($line->amount_minor),
                    $isCredit ? $format($line->amount_minor) : '',
                    $format($signed),
                    $format($line->balance_after_minor),
                    $account->currency,
                ];
            }
        }

        $closing = $opening + $change;
        $first = $accounts->first();
        $totalCurrency = $this->currencies->get($first instanceof \App\Models\LedgerAccount ? $first->currency : 'EGP');
        $total = static fn (int $minor): string => Money::of($minor, $totalCurrency)->toDecimal();
        $rows[] = [];
        $rows[] = ['opening_balance', '', '', '', '', '', '', $total($opening), $totalCurrency->code];
        $rows[] = ['total_credits', '', '', '', '', $total($credits), '', '', $totalCurrency->code];
        $rows[] = ['total_debits', '', '', '', $total($debits), '', '', '', $totalCurrency->code];
        $rows[] = ['closing_balance', '', '', '', '', '', '', $total($closing), $totalCurrency->code];
        $rows[] = ['ledger_balance_now', '', '', '', '', '', '', $total($ledger), $totalCurrency->code];

        $this->audit->record(AuditAction::Exported, $provider, $actor, meta: [
            'kind' => 'provider_statement',
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'lines' => count($rows) - 6,
            'opening_minor' => $opening,
            'closing_minor' => $closing,
        ]);

        return ['rows' => $rows, 'totals' => ['opening_minor' => $opening, 'credits_minor' => $credits, 'debits_minor' => $debits, 'closing_minor' => $closing, 'ledger_balance_minor' => $ledger]];
    }

    private function postedAt(JournalLine $line, string $zone): string
    {
        return CarbonImmutable::instance($line->entry->posted_at)->setTimezone($zone)->format('Y-m-d H:i:s');
    }
}
