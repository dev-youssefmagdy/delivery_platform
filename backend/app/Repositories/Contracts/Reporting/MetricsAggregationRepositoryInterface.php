<?php

namespace App\Repositories\Contracts\Reporting;

use App\Enums\Reporting\MetricSet;
use Carbon\CarbonInterface;

/**
 * Reads the operational, payment and ledger tables for one market day and writes the daily metrics tables (P5.3). Every write REPLACES
 * the rows of its (day, city) or (day, timezone) in one transaction, so a rerun never adds twice.
 */
interface MetricsAggregationRepositoryInterface
{
    /**
     * @return array<int, string> city id => IANA timezone of the active and inactive cities that can have bookings
     */
    public function cityTimezones(): array;

    /**
     * Booking, offer and dispatch facts of the requests created in [start, end) in one city, per zone / category / currency.
     *
     * @return list<array<string, mixed>>
     */
    public function bookingFacts(int $cityId, CarbonInterface $startUtc, CarbonInterface $endUtc): array;

    /**
     * Payment, ledger and coupon facts of the day (by the day each event happened) for one city, per category / currency.
     *
     * @return list<array<string, mixed>>
     */
    public function financeFacts(int $cityId, CarbonInterface $startUtc, CarbonInterface $endUtc): array;

    /**
     * Support, safety and growth facts of the day (not tied to a city).
     *
     * @return array<string, int>
     */
    public function operationsFacts(CarbonInterface $startUtc, CarbonInterface $endUtc): array;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function replaceBookingMetrics(string $date, int $cityId, array $rows, CarbonInterface $now): int;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function replaceFinanceMetrics(string $date, int $cityId, array $rows, CarbonInterface $now): int;

    /**
     * @param array<string, int> $facts
     */
    public function replaceOperationsMetrics(string $date, string $timezone, array $facts, CarbonInterface $now): int;

    public function recordRun(MetricSet $set, CarbonInterface $at, string $coveredThrough, int $rows, int $durationMs): void;
}
