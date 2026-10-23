<?php

namespace App\Services\Operations;

use App\Events\Realtime\OperationsFeedEvent;
use App\Services\Dispatch\EventSequencer;
use Illuminate\Contracts\Cache\Repository;

/**
 * Publishes the live operations feed (SRS §16.6). The numbers are one stream-wide sequence so the admin map can notice
 * a gap and refetch; provider moves are throttled per provider because drivers report every few seconds.
 */
final class OperationsFeedPublisher
{
    public function __construct(
        private readonly EventSequencer $sequencer,
        private readonly Repository $cache,
    ) {}

    public function bookingChanged(string $bookingUuid, string $status, ?string $previousStatus = null): void
    {
        $this->publish(OperationsFeedEvent::BOOKING_CHANGED, ['uuid' => $bookingUuid, 'status' => $status, 'previous_status' => $previousStatus]);
    }

    public function safetyIncident(string $incidentUuid, string $priority, string $status): void
    {
        $this->publish(OperationsFeedEvent::SAFETY_INCIDENT, ['uuid' => $incidentUuid, 'priority' => $priority, 'status' => $status]);
    }

    public function providerChanged(string $providerUuid, bool $online): void
    {
        $this->publish(OperationsFeedEvent::PROVIDER_CHANGED, ['uuid' => $providerUuid, 'online' => $online]);
    }

    public function providerMoved(string $providerUuid, float $lat, float $lng, bool $busy): void
    {
        $seconds = max(1, (int) config('booking.operations.map_move_throttle_seconds', 10));

        if (! $this->cache->add('operations-feed:move:'.$providerUuid, 1, $seconds)) {
            return;
        }

        $this->publish(OperationsFeedEvent::PROVIDER_MOVED, ['uuid' => $providerUuid, 'lat' => round($lat, 5), 'lng' => round($lng, 5), 'busy' => $busy]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function publish(string $type, array $payload): void
    {
        OperationsFeedEvent::dispatch($type, $this->sequencer->next('operations'), $payload);
    }
}
