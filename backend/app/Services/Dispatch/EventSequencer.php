<?php

namespace App\Services\Dispatch;

/**
 * Per-booking, strictly increasing event numbers so clients can detect a missed event (ADR-07). The default
 * implementation counts in the cache; P2.3 may replace it with the number stored by the `booking_status_events` outbox.
 */
interface EventSequencer
{
    public function next(string $bookingUuid): int;
}
