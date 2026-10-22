<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ReportData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Services\Support\TicketService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * "Report an issue" on a booking (SRS §6.7, §13.1). Since P3.7 a report is a real support ticket (source `report`) in the
 * ticket inbox; the P2.5 endpoint keeps working and answers with the ticket. Allowed to either side of a booking that had a
 * provider, at any point of or after the trip, with no rating required first. A per-day limit per person and booking stops
 * abuse without blocking urgent reports.
 */
final class BookingReportService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly TicketService $support,
    ) {}

    public function reportAsCustomer(User $customer, string $bookingUuid, ReportData $data): SupportTicket
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->store($booking, ActorType::Customer, $customer, $data);
    }

    public function reportAsProvider(ProviderProfile $provider, string $bookingUuid, ReportData $data): SupportTicket
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
        $provider->loadMissing('user');

        return $this->store($booking, ActorType::Provider, $provider->user, $data);
    }

    public static function canReport(Booking $booking): bool
    {
        return $booking->provider_id !== null && ! in_array($booking->status, [BookingStatus::Draft, BookingStatus::SearchingOffers, BookingStatus::OffersReceived, BookingStatus::ExpiredNoProvider], true);
    }

    private function store(Booking $booking, ActorType $reporter, User $user, ReportData $data): SupportTicket
    {
        if (! self::canReport($booking)) {
            throw BusinessRuleViolation::make('report_not_available');
        }

        $limit = (int) config('booking.trip.reports_per_day', 5);

        if ($this->tickets->countReportsSince($booking->id, $user->id, CarbonImmutable::now()->subDay()) >= $limit) {
            throw BusinessRuleViolation::make('report_limit_reached', context: ['max' => $limit]);
        }

        return $this->support->openFromReport($booking, $reporter, $user, $data->category, $data->description);
    }
}
