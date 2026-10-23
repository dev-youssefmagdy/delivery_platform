<?php

namespace App\Http\Resources\Api\V1\Coupon;

use App\Models\CouponReservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One use of a coupon in the customer's history: which coupon, for which booking, what became of it and the discount.
 *
 * @property CouponReservation $resource
 */
class CouponUsageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reservation = $this->resource;
        $amount = $reservation->redemption->discount_minor ?? $reservation->hold_minor;

        return [
            'id' => $reservation->uuid,
            'status' => $reservation->status->value,
            'coupon' => ['code' => $reservation->coupon->code, 'title' => $reservation->coupon->title],
            'booking' => $reservation->booking === null ? null : $reservation->booking->uuid,
            'discount' => ['amount_minor' => $amount, 'currency' => $reservation->currency, 'final' => $reservation->redemption !== null],
            'reserved_at' => $reservation->reserved_at->toIso8601ZuluString(),
        ];
    }
}
