<?php

namespace App\Events\Realtime;

/**
 * The trip start was locked after too many wrong PIN entries and the booking is flagged for support. Sent to the
 * customer and the provider channels as `booking.support_flagged`.
 */
final class TripSupportFlagged extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.support_flagged';
    }
}
