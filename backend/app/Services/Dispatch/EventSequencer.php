<?php

namespace App\Services\Dispatch;

/**
 * Per-booking, strictly increasing event numbers so clients can detect a missed event (ADR-07). Booking streams
 * use `bookings.sequence` (BookingEventSequencer, the same number the status events carry); other streams count in the cache.
 */
interface EventSequencer
{
    public function next(string $bookingUuid): int;
}
