<?php

namespace App\Http\Resources\Api\V1\Booking;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the customer's booking history (GET /bookings).
 *
 * @property Booking $resource
 */
class BookingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::summary($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(Booking $booking): array
    {
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
            'proposed_fare' => ['amount_minor' => $booking->proposed_fare_minor, 'currency' => $booking->currency],
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'created_at' => $booking->created_at->toIso8601ZuluString(),
        ];
    }
}
