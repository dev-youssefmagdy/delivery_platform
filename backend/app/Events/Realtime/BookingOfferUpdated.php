<?php

namespace App\Events\Realtime;

/**
 * An offer changed: a new version for the customer, a decision for the provider. Event `booking.offer_updated`.
 */
final class BookingOfferUpdated extends OfferEvent
{
    public function eventName(): string
    {
        return 'booking.offer_updated';
    }
}
