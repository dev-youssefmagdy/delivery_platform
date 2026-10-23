<?php

namespace App\Console\Commands;

use App\Jobs\Reporting\AggregateDailyMetrics as AggregateDailyMetricsJob;
use App\Services\Reporting\MetricsAggregationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * `metrics:aggregate-daily`: rebuilds the daily metrics behind the dashboard and the reports. Hourly (today and yesterday) and nightly
 * (`--nightly`, the last seven days); `--from/--to` backfills a range. Every rebuild replaces its (day, city) rows, so rerunning is safe.
 */
class AggregateDailyMetrics extends Command
{
    protected $signature = 'metrics:aggregate-daily {--nightly : Rebuild the last seven days} {--days= : Rebuild this many recent days} {--from= : First market day (Y-m-d) of a backfill} {--to= : Last market day (Y-m-d), default today} {--city= : Only this city id} {--queue : Run on the queue instead of now}';

    protected $description = 'Rebuild the daily metrics tables (bookings, finance, operations) idempotently';

    public function handle(MetricsAggregationService $aggregation): int
    {
        $city = $this->option('city') === null ? null : (int) $this->option('city');
        $from = $this->option('from');
        $days = $this->option('days') !== null ? max(1, min(400, (int) $this->option('days'))) : ($this->option('nightly') ? MetricsAggregationService::NIGHTLY_DAYS : MetricsAggregationService::HOURLY_DAYS);

        if ($this->option('queue')) {
            AggregateDailyMetricsJob::dispatch($days, $city);
            $this->info('Queued.');

            return self::SUCCESS;
        }

        if (is_string($from) && $from !== '') {
            $to = is_string($this->option('to')) && $this->option('to') !== '' ? CarbonImmutable::parse((string) $this->option('to')) : CarbonImmutable::now();
            $result = $aggregation->aggregateRange(CarbonImmutable::parse($from), $to, $city);
        } else {
            $result = $aggregation->aggregateRecent($days, $city);
        }

        $this->info(sprintf('%d rows written for %d day(s) in %d city(ies).', $result['rows'], $result['days'], $result['cities']));

        return self::SUCCESS;
    }
}
