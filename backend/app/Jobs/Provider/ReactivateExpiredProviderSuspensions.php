<?php

namespace App\Jobs\Provider;

use App\Enums\Common\QueueName;
use App\Services\Provider\ProviderAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled every minute: ends temporary provider suspensions whose expiry has passed (audited as the system).
 * Idempotent and safe to run concurrently with manual reactivations (each provider is re-checked under a row lock).
 */
class ReactivateExpiredProviderSuspensions implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(QueueName::Default->value);
    }

    public function handle(ProviderAccountService $accounts): void
    {
        $accounts->reactivateExpired();
    }
}
