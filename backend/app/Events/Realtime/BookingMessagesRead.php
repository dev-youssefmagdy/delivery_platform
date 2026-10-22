<?php

namespace App\Events\Realtime;

/**
 * The other side opened the conversation: delivered to the sender's own channel as `booking.messages_read` so the read receipt
 * updates live.
 */
final class BookingMessagesRead extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.messages_read';
    }
}
