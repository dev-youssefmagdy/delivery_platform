<?php

namespace App\Events\Realtime;

use App\Enums\Common\QueueName;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base of every realtime event (SRS §10.4, ADR-07): `{booking_uuid, sequence, occurred_at, payload}` on a private
 * channel, broadcast after the transaction commits. WebSockets only deliver; clients refetch when `sequence` shows a
 * gap, and nothing here is the source of truth.
 */
abstract class RealtimeEvent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;

    public readonly string $occurredAt;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly ?string $bookingUuid,
        public readonly int $sequence,
        public readonly array $payload = [],
    ) {
        $this->occurredAt = CarbonImmutable::now()->toIso8601String();
    }

    abstract public function eventName(): string;

    public function broadcastAs(): string
    {
        return $this->eventName();
    }

    public function broadcastQueue(): string
    {
        return QueueName::Critical->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'booking_uuid' => $this->bookingUuid,
            'sequence' => $this->sequence,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }
}
