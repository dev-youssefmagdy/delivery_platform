<?php

namespace App\DTOs\Provider;

use App\Enums\Location\OfflineReason;
use App\Enums\Location\TrackingState;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * The provider's availability as the driver app shows it, with the tracking state that drives the app's sampling rate.
 */
final readonly class AvailabilityState
{
    public function __construct(
        public bool $online,
        public ?Carbon $onlineSince,
        public ?Vehicle $vehicle,
        public TrackingState $trackingState,
        public ?OfflineReason $lastOfflineReason,
        public int $staleAfterSeconds,
        public ?string $activeBookingUuid,
    ) {}
}
