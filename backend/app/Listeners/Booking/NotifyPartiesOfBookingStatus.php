<?php

namespace App\Listeners\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Notification\NotificationEvent;
use App\Events\Booking\BookingStatusTransitioned;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Notification\PartyNotifier;

/**
 * Tells the customer how the trip is going (SRS §14.1): provider chosen, on the way, arrived, trip started; and tells the
 * chosen provider that the customer picked them. Cancellations, completion, search expiry and operator actions are
 * announced by the flows that know the fee and the actor (`BookingNotifier`). Runs after the transition committed and
 * deduplicates by the transition's sequence.
 */
final class NotifyPartiesOfBookingStatus
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly PartyNotifier $parties,
    ) {}

    public function handle(BookingStatusTransitioned $event): void
    {
        $customerEvent = match ($event->to) {
            BookingStatus::ProviderSelected => NotificationEvent::BookingProviderSelected,
            BookingStatus::ProviderHeadingToPickup => NotificationEvent::BookingProviderHeading,
            BookingStatus::ProviderArrived => NotificationEvent::BookingProviderArrived,
            BookingStatus::InProgress => $event->from === BookingStatus::ProviderArrived ? NotificationEvent::BookingTripStarted : null,
            default => null,
        };

        if ($customerEvent === null) {
            return;
        }

        $booking = $this->bookings->find($event->bookingId);

        if (! $booking instanceof Booking) {
            return;
        }

        $this->parties->customer($booking, $customerEvent, (string) $event->sequence);
    }
}
