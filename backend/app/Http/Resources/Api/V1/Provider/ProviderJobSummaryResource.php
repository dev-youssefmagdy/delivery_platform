<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the provider's job history (GET /provider/bookings): what the driver needs for a list, no phone numbers
 * and no addresses (the detail endpoint serves those for the job the provider holds).
 *
 * @property Booking $resource
 */
class ProviderJobSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $category = (array) ($booking->service_snapshot['category'] ?? []);
        $amount = static fn (?int $minor): ?array => $minor === null ? null : ['amount_minor' => $minor, 'currency' => $booking->currency];

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
            'customer' => ['name' => $booking->customer->name],
            'agreed_fare' => $amount($booking->agreed_fare_minor),
            'final_fare' => $amount($booking->final_fare_minor),
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'selected_at' => $booking->provider_selected_at?->toIso8601ZuluString(),
            'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601ZuluString(),
            'created_at' => $booking->created_at->toIso8601ZuluString(),
        ];
    }
}
