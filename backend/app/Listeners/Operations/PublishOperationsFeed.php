<?php

namespace App\Listeners\Operations;

use App\Events\Realtime\BookingStatusChanged;
use App\Events\Realtime\ProviderAvailabilityChanged;
use App\Events\Realtime\ProviderPositionRecorded;
use App\Services\Operations\OperationsFeedPublisher;

/**
 * Mirrors booking status changes, provider availability changes and provider positions onto the admin operations feed.
 * Runs after the originating transaction committed (those events are dispatched after commit).
 */
final class PublishOperationsFeed
{
    public function __construct(private readonly OperationsFeedPublisher $feed) {}

    public function handle(BookingStatusChanged|ProviderAvailabilityChanged|ProviderPositionRecorded $event): void
    {
        match (true) {
            $event instanceof BookingStatusChanged => $this->feed->bookingChanged(
                (string) $event->bookingUuid,
                (string) ($event->payload['status'] ?? ''),
                isset($event->payload['previous_status']) ? (string) $event->payload['previous_status'] : null,
            ),
            $event instanceof ProviderAvailabilityChanged => $this->feed->providerChanged($event->providerUuid, (bool) ($event->payload['online'] ?? false)),
            default => $this->feed->providerMoved($event->providerUuid, $event->lat, $event->lng, $event->busy),
        };
    }
}
