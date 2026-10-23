<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Delivered on `private-admin.operations` (gated by `bookings.view`) as `operations.{type}`: a booking changed status,
 * a provider went online or offline, a provider moved. Hints for the live map only: ids, coordinates and statuses, never
 * names, phones or addresses. The map refetches its snapshot when it sees a gap or a booking it does not know.
 */
final class OperationsFeedEvent extends RealtimeEvent
{
    public const BOOKING_CHANGED = 'booking_changed';

    public const PROVIDER_CHANGED = 'provider_changed';

    public const PROVIDER_MOVED = 'provider_moved';

    /** A safety case was raised or changed (ids and levels only; the case itself needs `incidents.view`). */
    public const SAFETY_INCIDENT = 'safety_incident';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public readonly string $type, int $sequence, array $payload = [])
    {
        parent::__construct(null, $sequence, $payload);
    }

    public function eventName(): string
    {
        return 'operations.'.$this->type;
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.operations')];
    }
}
