<?php

namespace App\Services\Reporting;

use App\Enums\Reporting\MetricSet;
use App\Repositories\Contracts\Reporting\MetricsAggregationRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Rebuilds the daily metrics (P5.3, ADR-178). A market day is the day in the CITY's timezone: its window is [local 00:00, next local 00:00)
 * converted to UTC, so daylight-saving days are 23 or 25 hours long and nothing is attributed to the wrong day. Every rebuild REPLACES
 * the rows of its (day, city) / (day, timezone), so rerunning a day, a range or the hourly and nightly jobs together is always safe.
 */
final class MetricsAggregationService
{
    /** Days rebuilt by the hourly run: today (in progress) and yesterday (late payments, ledger postings, refunds). */
    public const HOURLY_DAYS = 2;

    /** Days rebuilt by the nightly run: covers late settlements and refunds. */
    public const NIGHTLY_DAYS = 7;

    public function __construct(private readonly MetricsAggregationRepositoryInterface $metrics) {}

    /**
     * Rebuilds the last `$days` market days up to today (each city's own today).
     *
     * @return array{rows: int, days: int, cities: int}
     */
    public function aggregateRecent(int $days, ?int $cityId = null, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $started = hrtime(true);
        $rows = 0;
        $cities = $this->cities($cityId);

        foreach ($cities as $id => $timezone) {
            $today = CarbonImmutable::instance($now)->setTimezone($timezone)->startOfDay();

            for ($offset = $days - 1; $offset >= 0; $offset--) {
                $rows += $this->aggregateCityDay($id, $timezone, $today->subDays($offset)->toDateString(), $now);
            }
        }

        $rows += $this->aggregateOperations($cities, $days, $now);
        $this->markRun($rows, $started);

        return ['rows' => $rows, 'days' => $days, 'cities' => count($cities)];
    }

    /**
     * Rebuilds an explicit range of market days (backfill / repair).
     *
     * @return array{rows: int, days: int, cities: int}
     */
    public function aggregateRange(CarbonInterface $from, CarbonInterface $to, ?int $cityId = null, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $started = hrtime(true);
        $rows = 0;
        $cities = $this->cities($cityId);
        $days = (int) CarbonImmutable::parse($from->toDateString())->diffInDays(CarbonImmutable::parse($to->toDateString())) + 1;

        foreach ($cities as $id => $timezone) {
            for ($day = CarbonImmutable::parse($from->toDateString()); $day->lte($to); $day = $day->addDay()) {
                $rows += $this->aggregateCityDay($id, $timezone, $day->toDateString(), $now);
            }
        }

        foreach (array_unique(array_values($cities)) as $timezone) {
            for ($day = CarbonImmutable::parse($from->toDateString()); $day->lte($to); $day = $day->addDay()) {
                $rows += $this->aggregateOperationsDay($timezone, $day->toDateString(), $now);
            }
        }

        $this->markRun($rows, $started, $to->toDateString());

        return ['rows' => $rows, 'days' => $days, 'cities' => count($cities)];
    }

    /**
     * Bookings and finance metrics of one city and market day. Returns the rows written.
     */
    public function aggregateCityDay(int $cityId, string $timezone, string $date, ?CarbonInterface $now = null): int
    {
        [$start, $end] = $this->window($date, $timezone);
        $now ??= now();

        return $this->metrics->replaceBookingMetrics($date, $cityId, $this->bookingRows($cityId, $start, $end), $now)
            + $this->metrics->replaceFinanceMetrics($date, $cityId, $this->metrics->financeFacts($cityId, $start, $end), $now);
    }

    public function aggregateOperationsDay(string $timezone, string $date, ?CarbonInterface $now = null): int
    {
        [$start, $end] = $this->window($date, $timezone);

        return $this->metrics->replaceOperationsMetrics($date, $timezone, $this->metrics->operationsFacts($start, $end), $now ?? now());
    }

    /**
     * UTC window of a market day.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function window(string $date, string $timezone): array
    {
        $start = CarbonImmutable::parse($date, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($date, $timezone)->addDay()->startOfDay();

        return [$start->utc(), $end->utc()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bookingRows(int $cityId, CarbonInterface $start, CarbonInterface $end): array
    {
        return array_map(static fn (array $row): array => [
            'service_zone_id' => (int) $row['zone_id'],
            'service_category_id' => (int) $row['category_id'],
            'currency' => (string) $row['currency'],
        ] + array_diff_key($row, ['zone_id' => 1, 'category_id' => 1, 'currency' => 1]), $this->metrics->bookingFacts($cityId, $start, $end));
    }

    /**
     * @param array<int, string> $cities
     */
    private function aggregateOperations(array $cities, int $days, CarbonInterface $now): int
    {
        $rows = 0;

        foreach (array_unique(array_values($cities)) as $timezone) {
            $today = CarbonImmutable::instance($now)->setTimezone($timezone)->startOfDay();

            for ($offset = $days - 1; $offset >= 0; $offset--) {
                $rows += $this->aggregateOperationsDay($timezone, $today->subDays($offset)->toDateString(), $now);
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function cities(?int $cityId): array
    {
        $all = $this->metrics->cityTimezones();

        return $cityId === null ? $all : array_intersect_key($all, [$cityId => true]);
    }

    private function markRun(int $rows, int $started, ?string $through = null): void
    {
        $through ??= now((string) config('admin.display_timezone', 'UTC'))->toDateString();
        $elapsed = (int) ((hrtime(true) - $started) / 1_000_000);

        foreach (MetricSet::cases() as $set) {
            $this->metrics->recordRun($set, now(), $through, $rows, $elapsed);
        }
    }
}
