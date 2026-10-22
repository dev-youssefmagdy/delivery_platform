<?php

namespace App\Console\Commands;

use App\Services\Courier\DeliveryAttemptService;
use Illuminate\Console\Command;

/**
 * `courier:escalate-attempts` (every minute): a failed delivery attempt that neither the customer nor the provider answered
 * within `courier.attempts.escalate_after_minutes` is handed to operations (flagged for support). Idempotent.
 */
class EscalateStaleDeliveryAttempts extends Command
{
    protected $signature = 'courier:escalate-attempts';

    protected $description = 'Hand failed delivery attempts nobody answered to operations';

    public function handle(DeliveryAttemptService $attempts): int
    {
        $this->info('Escalated: '.$attempts->escalateStale());

        return self::SUCCESS;
    }
}
