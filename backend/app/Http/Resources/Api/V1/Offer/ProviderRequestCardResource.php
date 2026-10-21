<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Support\Booking\CounterOfferBounds;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A request as a dispatched provider may see it BEFORE the customer chose anybody (staged disclosure, Plan I10, SRS
 * §7.3): service, rounded pickup and drop-off areas (about 1 km), area name, distance to pickup, proposed fare,
 * route size, passengers / load, expiry and the counter-offer bounds. Never the exact address, coordinates to the
 * metre, the customer's name or phone, or the free-text service details (which can hold names and contacts).
 * The assigned provider sees the full booking through `ProviderAssignedBookingResource` instead.
 *
 * @property Booking $resource
 */
class ProviderRequestCardResource extends JsonResource
{
    public function __construct(Booking $booking, private readonly ?BookingOffer $myOffer = null, private readonly ?int $distanceToPickupM = null)
    {
        parent::__construct($booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $locale = app()->getLocale();
        $category = (array) ($booking->service_snapshot['category'] ?? []);
        $zoneName = (array) ($booking->service_snapshot['zone']['name'] ?? []);
        $cityName = (array) ($booking->service_snapshot['city']['name'] ?? []);
        $route = (array) ($booking->route_snapshot ?? []);
        $bounds = CounterOfferBounds::for($booking);

        return [
            'id' => $booking->uuid,
            'status' => $booking->status->value,
            'booking_type' => $booking->booking_type->value,
            'service' => [
                'id' => $booking->service_category_id,
                'code' => $category['code'] ?? null,
                'name' => BookingResource::names((array) ($category['name'] ?? [])),
            ],
            'pickup_area' => [
                'name' => $zoneName[$locale] ?? $zoneName['ar'] ?? $cityName[$locale] ?? $cityName['ar'] ?? null,
                'lat' => round($booking->pickup_point->lat, 2),
                'lng' => round($booking->pickup_point->lng, 2),
            ],
            'dropoff_area' => $booking->dropoff_point === null ? null : [
                'lat' => round($booking->dropoff_point->lat, 2),
                'lng' => round($booking->dropoff_point->lng, 2),
            ],
            'distance_to_pickup_m' => $this->distanceToPickupM,
            'proposed_fare' => ['amount_minor' => $booking->proposed_fare_minor, 'currency' => $booking->currency],
            'counter_offer' => [
                'allowed' => CounterOfferBounds::negotiationEnabled($booking),
                'min_minor' => $bounds['min'],
                'max_minor' => $bounds['max'],
            ],
            'details' => [
                'distance_m' => $route['distance_m'] ?? null,
                'duration_s' => $route['duration_s'] ?? null,
                'stops' => (int) ($booking->policy_snapshot['stops_count'] ?? 0),
                'passengers' => $booking->passengers,
                'luggage_count' => $booking->luggage_count,
                'cargo_weight_kg' => $booking->cargo_weight_kg,
                'scheduled_at' => $booking->scheduled_at?->toIso8601ZuluString(),
                'payment_method' => $booking->payment_method->value,
                'restricted_pickup' => (bool) ($booking->policy_snapshot['zone']['restricted_pickup'] ?? false),
            ],
            'expires_at' => $booking->search_expires_at?->toIso8601ZuluString(),
            'my_offer' => $this->myOffer === null ? null : (new ProviderOfferResource($this->myOffer))->resolve(),
        ];
    }
}
