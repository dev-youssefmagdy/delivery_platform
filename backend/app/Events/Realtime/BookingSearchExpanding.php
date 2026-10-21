<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Delivered on `private-booking.{bookingUuid}` as `booking.search_expanding`.
 */
final class BookingSearchExpanding extends RealtimeEvent
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
        return 'booking.search_expanding';
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('booking.'.$this->bookingUuid)];
    }
}
