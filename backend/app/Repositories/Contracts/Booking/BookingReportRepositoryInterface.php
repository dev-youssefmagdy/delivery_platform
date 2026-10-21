<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingReport;
use Carbon\CarbonInterface;

interface BookingReportRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): BookingReport;

    /**
     * Reports of one reporter on one booking created at or after `$since` (the daily limit).
     */
    public function countSince(int $bookingId, string $reporterType, int $reporterId, CarbonInterface $since): int;
}
