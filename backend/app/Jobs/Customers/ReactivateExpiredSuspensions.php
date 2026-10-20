<?php

namespace App\Jobs\Customers;

use App\Enums\Common\QueueName;
use App\Services\Customers\CustomerAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled every minute: ends temporary customer suspensions whose expiry has passed (audited as the system).
 * Idempotent and safe to run concurrently with manual reactivations (each account is re-checked under a row lock).
 */
class ReactivateExpiredSuspensions implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(QueueName::Default->value);
    }

    public function handle(CustomerAccountService $accounts): void
    {
        $accounts->reactivateExpired();
    }
}
