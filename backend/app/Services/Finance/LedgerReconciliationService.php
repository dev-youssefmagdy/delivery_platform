<?php

namespace App\Services\Finance;

use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\EntrySide;
use App\Enums\Finance\ReconciliationStatus;
use App\Models\Admin;
use App\Models\LedgerAccount;
use App\Models\LedgerReconciliationRun;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerReconciliationRunRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

/**
 * `ledger:reconcile` (Plan I5, I6): proves the cached balance of every account equals the sum of its lines, that every
 * entry balances in every currency and that every line has its account's currency. It REPORTS: findings are stored in a
 * run record, logged at critical level (the alert) and shown in the admin; nothing is ever corrected automatically.
 *
 * The comparison reads accounts and lines inside one repeatable-read transaction, so a posting that commits while the
 * run is in progress cannot produce a false mismatch.
 */
final class LedgerReconciliationService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalLineRepositoryInterface $lines,
        private readonly LedgerReconciliationRunRepositoryInterface $runs,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function run(?Admin $actor = null): LedgerReconciliationRun
    {
        [$findings, $accountsChecked, $entriesChecked] = $this->db->transaction(fn (): array => $this->compare());

        $run = $this->runs->create([
            'status' => $findings === [] ? ReconciliationStatus::Ok : ReconciliationStatus::Mismatch,
            'accounts_checked' => $accountsChecked,
            'entries_checked' => $entriesChecked,
            'findings' => $findings === [] ? null : $findings,
            'triggered_by_type' => $actor instanceof Admin ? ActorType::Admin->value : ActorType::System->value,
            'triggered_by_id' => $actor?->id,
            'ran_at' => CarbonImmutable::now(),
        ]);

        if ($findings !== []) {
            Log::critical('ledger.reconcile.mismatch', ['run' => $run->uuid, 'findings' => count($findings), 'first' => $findings[0]]);
        }

        if ($actor instanceof Admin) {
            $this->audit->record(AuditAction::Reconciled, $run, $actor, after: ['status' => $run->status->value, 'findings' => count($findings)]);
        }

        return $run;
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int}
     */
    private function compare(): array
    {
        $findings = [];
        $accounts = $this->accounts->allForReconciliation();
        $totals = $this->lines->totalsByAccount();

        /** @var LedgerAccount $account */
        foreach ($accounts as $account) {
            $debit = $totals[$account->id]['debit'] ?? 0;
            $credit = $totals[$account->id]['credit'] ?? 0;
            $expected = $account->type->normalSide() === EntrySide::Debit ? $debit - $credit : $credit - $debit;

            if ($expected !== $account->balance_minor) {
                $findings[] = [
                    'kind' => 'balance_mismatch',
                    'account_uuid' => $account->uuid,
                    'account_type' => $account->type->value,
                    'currency' => $account->currency,
                    'expected_minor' => $expected,
                    'cached_minor' => $account->balance_minor,
                ];
            }
        }

        foreach ($this->lines->unbalancedEntries() as $entry) {
            $findings[] = ['kind' => 'unbalanced_entry', 'entry_uuid' => $entry['uuid'], 'currency' => $entry['currency'], 'difference_minor' => $entry['difference']];
        }

        foreach ($this->lines->currencyMismatches() as $line) {
            $findings[] = ['kind' => 'currency_mismatch', 'entry_uuid' => $line['entry_uuid'], 'account_uuid' => $line['account_uuid'], 'line_currency' => $line['line'], 'account_currency' => $line['account']];
        }

        return [$findings, $accounts->count(), $this->entries->count()];
    }
}
