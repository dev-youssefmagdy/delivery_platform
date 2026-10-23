<?php

namespace App\Repositories\Contracts\Reporting;

use App\DTOs\Reporting\ReportFilterData;
use App\DTOs\Reporting\ReportingRange;
use App\Enums\Reporting\MetricSet;
use Carbon\CarbonInterface;

/**
 * Read side of the operations dashboard (P5.3): sums and series of the daily metrics tables for a market-day range, plus the cheap live
 * counters of the current queue state. Every figure is a count or an integer minor-unit sum; no row carries personal data.
 */
interface ReportingRepositoryInterface
{
    /**
     * Sums of the booking metrics over the range (optionally restricted to cities, zones, categories, a timezone).
     *
     * @return array<string, int>
     */
    public function bookingTotals(ReportFilterData $filters, ReportingRange $range): array;

    /**
     * Sums of the finance metrics over the range for one currency.
     *
     * @return array<string, int>
     */
    public function financeTotals(ReportFilterData $filters, ReportingRange $range, string $currency): array;

    /**
     * Per market day: requests, completed, cancelled, expired (zero-filled by the caller).
     *
     * @return array<string, array<string, int>> Y-m-d => measures
     */
    public function bookingSeries(ReportFilterData $filters): array;

    /**
     * Per market day finance measures of one currency.
     *
     * @return array<string, array<string, int>>
     */
    public function financeSeries(ReportFilterData $filters, string $currency): array;

    /**
     * Requests per service category over the range (category id => requests).
     *
     * @return array<int, int>
     */
    public function requestsByCategory(ReportFilterData $filters): array;

    /**
     * Bookings created in the range grouped by their CURRENT status (live read of the bookings table).
     *
     * @return array<string, int> status value => bookings
     */
    public function bookingsByStatus(ReportFilterData $filters): array;

    /**
     * Currencies that have finance metrics in the range, most used first.
     *
     * @return list<string>
     */
    public function currencies(ReportFilterData $filters): array;

    /**
     * Live operational counters: active (online) drivers, waiting requests, open tickets, pending verifications, pending payouts.
     *
     * @return array{active_drivers: int, waiting_requests: int, open_tickets: int, pending_verifications: int, pending_payouts: int, pending_payouts_minor: array<string, int>}
     */
    public function liveCounters(): array;

    public function lastAggregatedAt(MetricSet $set): ?CarbonInterface;

    /**
     * The market timezones of the cities (for the timezone filter), keyed by timezone name.
     *
     * @return list<string>
     */
    public function marketTimezones(): array;
}
