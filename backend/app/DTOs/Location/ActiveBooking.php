<?php

namespace App\DTOs\Location;

use App\Enums\Location\TrackingState;

/**
 * The booking a provider is serving right now and how closely it is tracked (`ToPickup` or `Active`).
 */
final readonly class ActiveBooking
{
    public function __construct(
        public int $bookingId,
        public string $bookingUuid,
        public TrackingState $trackingState,
    ) {}
}
