<?php

namespace App\Events\Realtime;

/**
 * The customer chose a provider: the customer gets the provider card, the winner gets the assignment. Event `booking.provider_selected`.
 */
final class BookingProviderSelected extends OfferEvent
{
    public function eventName(): string
    {
        return 'booking.provider_selected';
    }
}
