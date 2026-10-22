<?php

namespace App\Http\Resources\Api\V1\Coupon;

use App\DTOs\Coupon\CouponQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The answer to POST /coupons/validate: valid or not with the explanation code and message, the discount the server
 * calculated. The funding split is internal and never returned.
 *
 * @property CouponQuote $resource
 */
class CouponQuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quote = $this->resource;
        $result = $quote->result;

        return [
            'valid' => $result->isEligible(),
            'reason_code' => $result->reason->value,
            'message' => $result->isEligible() ? __('api.coupon.valid') : (string) __('api.errors.coupon_'.$result->reason->value),
            'coupon' => $quote->coupon === null || ! $result->isEligible() ? null : new CouponSummaryResource($quote->coupon),
            'discount' => ['amount_minor' => $result->discount->amount, 'currency' => $result->discount->currency->code],
        ];
    }
}
