<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ReportData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\ReportStatus;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\BookingReport;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingReportRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * "Report an issue" on a booking (SRS §6.7, §13.1). Stores a real report for support (`booking_reports`); P3.7 turns open
 * rows into support tickets. Allowed to either side of a booking that had a provider, at any point of or after the trip,
 * with no rating required first. A per-day limit per person and booking stops abuse without blocking urgent reports.
 */
final class BookingReportService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingReportRepositoryInterface $reports,
    ) {}

    public function reportAsCustomer(User $customer, string $bookingUuid, ReportData $data): BookingReport
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->store($booking, ActorType::Customer, $customer->id, $data);
    }

    public function reportAsProvider(ProviderProfile $provider, string $bookingUuid, ReportData $data): BookingReport
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->store($booking, ActorType::Provider, $provider->id, $data);
    }

    public static function canReport(Booking $booking): bool
    {
        return $booking->provider_id !== null && ! in_array($booking->status, [BookingStatus::Draft, BookingStatus::SearchingOffers, BookingStatus::OffersReceived, BookingStatus::ExpiredNoProvider], true);
    }

    private function store(Booking $booking, ActorType $reporter, int $reporterId, ReportData $data): BookingReport
    {
        if (! self::canReport($booking)) {
            throw BusinessRuleViolation::make('report_not_available');
        }

        $limit = (int) config('booking.trip.reports_per_day', 5);

        if ($this->reports->countSince($booking->id, $reporter->value, $reporterId, CarbonImmutable::now()->subDay()) >= $limit) {
            throw BusinessRuleViolation::make('report_limit_reached', context: ['max' => $limit]);
        }

        return $this->reports->create([
            'booking_id' => $booking->id,
            'reporter_type' => $reporter->value,
            'reporter_id' => $reporterId,
            'category' => $data->category,
            'description' => trim($data->description),
            'status' => ReportStatus::Open,
        ]);
    }
}
