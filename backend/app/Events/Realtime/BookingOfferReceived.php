<?php

namespace App\Events\Realtime;

/**
 * A provider's first offer reached the customer (card payload). Event `booking.offer_received`.
 */
final class BookingOfferReceived extends OfferEvent
{
    public function eventName(): string
    {
        return 'booking.offer_received';
    }
}
