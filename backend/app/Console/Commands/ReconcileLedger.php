<?php

namespace App\Console\Commands;

use App\Services\Finance\LedgerReconciliationService;
use Illuminate\Console\Command;

/**
 * `ledger:reconcile` (nightly): compares every account's cached balance with the sum of its journal lines and checks
 * that every entry balances. Report only: a mismatch is stored, logged at critical level and shown in the admin
 * reconciliation page; the exit code is non-zero so a scheduler or CI notices. Nothing is ever fixed automatically.
 */
class ReconcileLedger extends Command
{
    protected $signature = 'ledger:reconcile';

    protected $description = 'Compare cached ledger balances with their journal lines (report only)';

    public function handle(LedgerReconciliationService $reconciliation): int
    {
        $run = $reconciliation->run();

        $this->line(sprintf('Run %s: %s (%d accounts, %d entries)', $run->uuid, $run->status->value, $run->accounts_checked, $run->entries_checked));

        foreach ($run->findings ?? [] as $finding) {
            $this->error((string) json_encode($finding, JSON_UNESCAPED_UNICODE));
        }

        return $run->findings === null ? self::SUCCESS : self::FAILURE;
    }
}
