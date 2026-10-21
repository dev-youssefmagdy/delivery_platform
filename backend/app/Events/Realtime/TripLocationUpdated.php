<?php

namespace App\Events\Realtime;

/**
 * The assigned provider's position, delivered to the customer's own channel as `booking.location_updated` and only
 * while the provider serves the booking (selected up to completion). Never sent on the booking channel, which other
 * dispatched providers can join.
 */
final class TripLocationUpdated extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.location_updated';
    }
}
