<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Base of events addressed to one party of a booking: the customer's own channel (`private-customer.{uuid}`) or the
 * provider's own (`private-provider.{uuid}`). Used for everything that must not reach other dispatched providers (offers,
 * and since P2.5 the trip lifecycle and live location, ADR-98); the payload always carries `booking_uuid` and the
 * booking sequence so a client can detect gaps and refetch.
 */
abstract class PartyEvent extends RealtimeEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    final public function __construct(public readonly string $channelName, ?string $bookingUuid, int $sequence, array $payload = [])
    {
        parent::__construct($bookingUuid, $sequence, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toCustomer(string $customerUuid, string $bookingUuid, int $sequence, array $payload): static
    {
        return new static('customer.'.$customerUuid, $bookingUuid, $sequence, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toProvider(string $providerUuid, string $bookingUuid, int $sequence, array $payload): static
    {
        return new static('provider.'.$providerUuid, $bookingUuid, $sequence, $payload);
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channelName)];
    }
}
