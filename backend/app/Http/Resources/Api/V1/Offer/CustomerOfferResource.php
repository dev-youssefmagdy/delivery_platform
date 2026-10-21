<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Models\BookingOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * One offer card for the customer (SRS §6.5). The amount comes from the offer's current version; `is_counter_offer`
 * tells a counter-offer apart from "I accept your fare". Needs `currentVersion`, `providerProfile.user` and
 * `vehicle.vehicleCategory` loaded. ETA and distance are shown only when the provider stated them or dispatch measured them.
 *
 * @property BookingOffer $resource
 */
class CustomerOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offer = $this->resource;
        $version = $offer->currentVersion;
        $selected = $offer->winner_marker !== null;

        return [
            'id' => $offer->uuid,
            'status' => $offer->status->value,
            'kind' => $offer->kind->value,
            'is_counter_offer' => $offer->kind->value === 'counter_offer',
            'amount' => $version === null ? null : ['amount_minor' => $version->amount_minor, 'currency' => $version->currency],
            'version' => $version?->version,
            'revision_count' => $offer->revision_count,
            'note' => $version?->note,
            'scope' => ($version?->scope ?? []) === [] ? new stdClass : $version?->scope,
            'eta_seconds' => $offer->eta_seconds,
            'distance_m' => $offer->distance_m,
            'expires_at' => $offer->expires_at->toIso8601ZuluString(),
            'provider' => ProviderIdentity::provider($offer->providerProfile),
            'vehicle' => ProviderIdentity::vehicle($offer->vehicle, $selected || (bool) config('booking.offers.show_plate_before_selection', true)),
            'created_at' => $offer->created_at->toIso8601ZuluString(),
        ];
    }
}
