<?php

namespace App\Events\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Delivered on `private-provider.{providerUuid}` as `provider.availability_changed`.
 */
final class ProviderAvailabilityChanged extends RealtimeEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public readonly string $providerUuid, ?string $bookingUuid, int $sequence, array $payload = [])
    {
        parent::__construct($bookingUuid, $sequence, $payload);
    }

    public function eventName(): string
    {
        return 'provider.availability_changed';
    }

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('provider.'.$this->providerUuid)];
    }
}
