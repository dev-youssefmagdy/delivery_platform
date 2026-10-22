<?php

namespace App\Http\Resources\Api\V1\Payout;

use App\Enums\Payments\PaymentMethodType;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One settled booking of the provider with the figures the ledger was posted from (SRS §7.6): gross fare, commission, discounts
 * by funder (a provider-funded coupon is already inside `provider_net`), tax, tip, net, total earned, cash collected and the
 * payment status. All amounts are server-calculated minor units.
 *
 * @property Booking $resource
 */
class EarningTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $b = (array) $booking->price_breakdown;
        $money = static fn (string $key): array => ['amount_minor' => (int) ($b[$key] ?? 0), 'currency' => $booking->currency];
        $category = (array) ($booking->service_snapshot['category'] ?? []);
        $cash = $booking->payment_method === PaymentMethodType::Cash;

        return [
            'booking' => ['id' => $booking->uuid, 'code' => $booking->code],
            'service' => ['code' => $category['code'] ?? null, 'name' => BookingResource::names((array) ($category['name'] ?? []))],
            'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            'gross_fare' => $money('provider_gross_minor'),
            'commission' => ['amount_minor' => (int) ($b['commission_minor'] ?? 0) + (int) ($b['tip_commission_minor'] ?? 0), 'currency' => $booking->currency],
            'discount_platform_funded' => $money('discount_platform_minor'),
            'discount_provider_funded' => $money('discount_provider_minor'),
            'tax' => $money('tax_minor'),
            'tip' => $money('tip_minor'),
            'provider_net' => $money('provider_net_minor'),
            'provider_earning' => $money('provider_earning_minor'),
            'cash_collected' => $cash ? $money('customer_payable_minor') : null,
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value, 'status_label' => $booking->payment_status->label()],
            'settled_at' => $booking->settled_at?->toIso8601ZuluString(),
        ];
    }
}
