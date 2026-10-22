<?php

namespace App\Events\Booking;

use App\Enums\Booking\BookingStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking moved to another status (dispatched after the transaction commits, by `BookingTransitionService` only).
 * Carries ids and the sequence of the transition so listeners reload the booking and stay idempotent.
 */
final class BookingStatusTransitioned implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $bookingId,
        public readonly BookingStatus $from,
        public readonly BookingStatus $to,
        public readonly int $sequence,
    ) {}
}
