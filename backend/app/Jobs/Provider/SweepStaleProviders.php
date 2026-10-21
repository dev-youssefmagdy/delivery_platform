<?php

namespace App\Jobs\Provider;

use App\Enums\Common\QueueName;
use App\Services\Provider\AvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled every 30 s: providers whose last position is older than the stale threshold go offline. Idempotent.
 */
class SweepStaleProviders implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(QueueName::Critical->value);
    }

    public function handle(AvailabilityService $availability): void
    {
        $availability->sweepStale();
    }
}
