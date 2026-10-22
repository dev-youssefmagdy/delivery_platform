<?php

namespace App\Services\Courier;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Catalog\BookingType;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingTransitionService;

/**
 * The chain of custody of a parcel (SRS §5.1-C, §7.5): picked_up, in_transit, arrived_dropoff, delivered, attempt_failed,
 * returning, returned. Each move is checked against `CustodyStatus::allowedNext()`, stored in `bookings.custody_status` and
 * appended to the booking timeline as a status-less event `custody_<value>` (through `BookingTransitionService::annotate`,
 * so timeline order is the booking's own sequence). This service never touches `bookings.status`.
 *
 * Every method must run inside a transaction that already holds the booking row lock.
 */
final class CustodyService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingTransitionService $transitions,
        private readonly BookingNotifier $notifier,
    ) {}

    /**
     * Custody is tracked for parcel bookings (and later for other physical-goods services that opt in).
     */
    public static function applies(Booking $booking): bool
    {
        return $booking->booking_type === BookingType::Parcel;
    }

    /**
     * @param array<string, mixed> $metadata non-PII facts of the event
     *
     * @throws ConflictException `custody_transition_invalid`
     */
    public function move(Booking $booking, CustodyStatus $to, TransitionContext $context, array $metadata = []): void
    {
        $from = $booking->custody_status;

        if ($from === null ? $to !== CustodyStatus::PickedUp : ! $from->canMoveTo($to)) {
            throw ConflictException::make('custody_transition_invalid', context: ['from' => $from?->value, 'to' => $to->value]);
        }

        $this->bookings->forceUpdate($booking, ['custody_status' => $to, 'lock_version' => $booking->lock_version + 1]);
        $this->transitions->annotate($booking, $to->timelineReason(), $context, ['custody_from' => $from?->value] + $metadata);
        $this->notifier->custody($booking->refresh(), $to);
    }
}
