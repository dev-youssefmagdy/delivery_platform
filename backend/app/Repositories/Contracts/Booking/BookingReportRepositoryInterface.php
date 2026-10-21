<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingReport;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface BookingReportRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): BookingReport;

    /**
     * Reports one side filed on the booking (personal-data export).
     *
     * @return Collection<int, BookingReport>
     */
    public function forReporterType(int $bookingId, string $reporterType): Collection;

    /**
     * Reports of one reporter on one booking created at or after `$since` (the daily limit).
     */
    public function countSince(int $bookingId, string $reporterType, int $reporterId, CarbonInterface $since): int;
}
