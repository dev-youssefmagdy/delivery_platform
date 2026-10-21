<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Delivered on `private-booking.{bookingUuid}` as `booking.status_changed`. Carries the new status only; clients
 * refetch the booking for the rest (events are hints, the database is the truth).
 */
final class BookingStatusChanged extends RealtimeEvent
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
        return 'booking.status_changed';
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('booking.'.$this->bookingUuid)];
    }
}
