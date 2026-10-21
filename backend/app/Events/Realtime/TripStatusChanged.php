<?php

namespace App\Events\Realtime;

/**
 * A status change of a booking, delivered to the party's own channel as `booking.status_changed` (the booking channel
 * gets the same event for web clients and admins). Carries the new status only; clients refetch for the rest.
 */
final class TripStatusChanged extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.status_changed';
    }
}
