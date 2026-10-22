<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentReconciliationService;
use App\Services\Payments\PaymentStatusSyncService;
use Illuminate\Console\Command;

/**
 * `payments:reconcile`: asks the gateway about payments that stayed unconfirmed past the timeout, then compares the
 * platform's online payments with the gateway's transactions and reports every difference (stored, logged critical, shown
 * in the admin). `--sweep-only` runs just the timeout sweep (cheap, scheduled every few minutes). The comparison never
 * changes a payment or the ledger.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--sweep-only : only handle payments past the confirmation timeout}';

    protected $description = 'Sweep unconfirmed payments and compare local payments with the gateway (report only)';

    public function handle(PaymentReconciliationService $reconciliation, PaymentStatusSyncService $sync): int
    {
        if ($this->option('sweep-only')) {
            $this->line(sprintf('Flagged for review: %d', $sync->sweepTimeouts()));

            return self::SUCCESS;
        }

        $run = $reconciliation->run();

        $this->line(sprintf('Run %s: %s (%d payments, %d gateway transactions, %d timed out)', $run->uuid, $run->status->value, $run->payments_checked, $run->transactions_checked, $run->timed_out));

        foreach ($run->findings ?? [] as $finding) {
            $this->error((string) json_encode($finding, JSON_UNESCAPED_UNICODE));
        }

        return $run->findings === null ? self::SUCCESS : self::FAILURE;
    }
}
