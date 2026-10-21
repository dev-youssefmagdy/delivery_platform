<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\BookingReport;
use App\Repositories\Contracts\Booking\BookingReportRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BookingReportRepository implements BookingReportRepositoryInterface
{
    public function create(array $attributes): BookingReport
    {
        return BookingReport::query()->create($attributes);
    }

    public function forReporterType(int $bookingId, string $reporterType): Collection
    {
        return BookingReport::query()->where('booking_id', $bookingId)->where('reporter_type', $reporterType)->orderBy('id')->get();
    }

    public function countSince(int $bookingId, string $reporterType, int $reporterId, CarbonInterface $since): int
    {
        return BookingReport::query()
            ->where('booking_id', $bookingId)
            ->where('reporter_type', $reporterType)
            ->where('reporter_id', $reporterId)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingReport::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }
}
