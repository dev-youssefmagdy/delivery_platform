<?php

namespace App\Events\Realtime;

/**
 * Delivered on the OTHER side's own private channel (`private-customer.{uuid}` or `private-provider.{uuid}`) as
 * `booking.message_received`. Never on the shared booking channel (dispatched providers can join that one) and never with the
 * text: the payload is a hint (message id, sender, unread count) and the app fetches the message over the authorized API.
 */
final class BookingMessageReceived extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.message_received';
    }
}
