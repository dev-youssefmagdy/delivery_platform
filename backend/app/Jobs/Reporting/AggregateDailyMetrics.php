<?php

namespace App\Jobs\Reporting;

use App\Enums\Common\QueueName;
use App\Services\Reporting\MetricsAggregationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued form of `metrics:aggregate-daily`. Idempotent (each rebuild replaces its day and city), so a retry or an overlapping run is safe.
 */
class AggregateDailyMetrics implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public readonly int $days = MetricsAggregationService::HOURLY_DAYS, public readonly ?int $cityId = null)
    {
        $this->onQueue(QueueName::Low->value);
    }

    public function handle(MetricsAggregationService $aggregation): void
    {
        $aggregation->aggregateRecent($this->days, $this->cityId);
    }
}
