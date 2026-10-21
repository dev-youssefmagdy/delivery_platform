<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Delivered on `private-booking.{bookingUuid}` as `booking.request_changed`: the customer changed the route or the
 * main details while searching, so earlier offers no longer apply.
 */
final class BookingRequestChanged extends RealtimeEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $bookingUuid, int $sequence, array $payload = [])
    {
        parent::__construct($bookingUuid, $sequence, $payload);
    }

    public function eventName(): string
    {
        return 'booking.request_changed';
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('booking.'.$this->bookingUuid)];
    }
}
