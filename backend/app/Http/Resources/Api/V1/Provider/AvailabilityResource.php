<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\DTOs\Provider\AvailabilityState;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The driver's availability, how the app must track position right now and the private channel to listen on.
 *
 * @property AvailabilityState $resource
 */
class AvailabilityResource extends JsonResource
{
    public function __construct(AvailabilityState $state, private readonly ProviderProfile $profile)
    {
        parent::__construct($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $state = $this->resource;

        return [
            'online' => $state->online,
            'online_since' => $state->onlineSince?->toIso8601ZuluString(),
            'vehicle_id' => $state->vehicle?->uuid,
            'last_offline_reason' => $state->lastOfflineReason?->value,
            'active_booking_id' => $state->activeBookingUuid,
            'tracking' => [
                'state' => $state->trackingState->value,
                'interval_seconds' => $state->trackingState->intervalSeconds(),
                'stale_after_seconds' => $state->staleAfterSeconds,
            ],
            'realtime' => [
                'channel' => 'private-provider.'.$this->profile->uuid,
            ],
        ];
    }
}
