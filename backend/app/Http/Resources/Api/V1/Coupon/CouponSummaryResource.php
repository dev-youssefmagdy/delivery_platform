<?php

namespace App\Http\Resources\Api\V1\Coupon;

use App\Models\Coupon;
use App\Services\Coupon\CouponDefinitionFactory;
use App\Support\Coupon\CouponPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the apps may show about a coupon: the localized texts, a one-line summary and the validity. Never the budget,
 * the counters or who funds it.
 *
 * @property Coupon $resource
 */
class CouponSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coupon = $this->resource;
        $coupon->loadMissing('scopes');
        $terms = app(CouponDefinitionFactory::class)->termsOf($coupon);
        $presenter = app(CouponPresenter::class);

        return [
            'id' => $coupon->uuid,
            'code' => $coupon->code,
            'automatic' => $coupon->isAutomatic(),
            'title' => $coupon->title,
            'description' => $coupon->description,
            'terms' => $coupon->legal_terms,
            'summary' => $presenter->discount($terms, $coupon->currency),
            'discount_type' => $coupon->discount_type->value,
            'starts_at' => $coupon->starts_at?->toIso8601ZuluString(),
            'ends_at' => $coupon->ends_at?->toIso8601ZuluString(),
        ];
    }
}
