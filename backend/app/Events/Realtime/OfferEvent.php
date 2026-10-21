<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Base of the negotiation events. Offers are competing, private data: the offer cards go to the customer's own
 * channel (`private-customer.{uuid}`), and a provider hears about its own offer on `private-provider.{uuid}`. The
 * booking channel is shared with every dispatched provider, so no offer data is ever sent there (Plan I10); the
 * payload always carries `booking_uuid` and the booking sequence so a client can detect gaps and refetch.
 */
abstract class OfferEvent extends RealtimeEvent
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
