<?php

namespace App\Events\Realtime;

/**
 * An offer ended without being chosen (expired, withdrawn, invalidated, lost to another provider). Event `booking.offer_expired`.
 */
final class BookingOfferExpired extends OfferEvent
{
    public function eventName(): string
    {
        return 'booking.offer_expired';
    }
}
