<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Models\Booking;
use App\Models\BookingStop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * The booking as its SELECTED provider sees it (staged disclosure, Plan I10): full addresses and stops, the service
 * details, the customer's name and phone, the agreed fare and scope. This resource is only built for the provider
 * whose offer was accepted; before selection a provider gets `ProviderRequestCardResource` and nothing else.
 * Needs `customer` and `stops` loaded.
 *
 * @property Booking $resource
 */
class ProviderAssignedBookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $category = (array) ($booking->service_snapshot['category'] ?? []);

        return [
            'id' => $booking->uuid,
            'code' => $booking->code,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'booking_type' => $booking->booking_type->value,
            'service' => [
                'id' => $booking->service_category_id,
                'code' => $category['code'] ?? null,
                'name' => BookingResource::names((array) ($category['name'] ?? [])),
            ],
            'customer' => [
                'name' => $booking->customer->name,
                'phone' => $booking->customer->phone_e164,
            ],
            'pickup' => $this->place($booking->pickup_point->lat, $booking->pickup_point->lng, $booking->pickup_address),
            'dropoff' => $booking->dropoff_point === null ? null : $this->place($booking->dropoff_point->lat, $booking->dropoff_point->lng, $booking->dropoff_address),
            'stops' => $booking->stops->map(fn (BookingStop $stop): array => ['position' => $stop->position] + $this->place($stop->point->lat, $stop->point->lng, $stop->address))->values()->all(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601ZuluString(),
            'passengers' => $booking->passengers,
            'service_details' => $booking->service_details === [] ? new stdClass : $booking->service_details,
            'route' => $booking->route_snapshot === null ? null : [
                'distance_m' => $booking->route_snapshot['distance_m'] ?? null,
                'duration_s' => $booking->route_snapshot['duration_s'] ?? null,
                'polyline' => $booking->route_snapshot['polyline'] ?? null,
            ],
            'agreed_fare' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => $booking->agreed_fare_minor, 'currency' => $booking->currency],
            'agreed_scope' => ($booking->agreed_scope ?? []) === [] ? new stdClass : $booking->agreed_scope,
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'pin_required' => (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false),
            'selected_at' => $booking->provider_selected_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @param array<string, mixed>|null $address
     * @return array{lat: float, lng: float, address: array<string, mixed>|null}
     */
    private function place(float $lat, float $lng, ?array $address): array
    {
        return ['lat' => round($lat, 6), 'lng' => round($lng, 6), 'address' => $address];
    }
}
