<?php

namespace App\DTOs\Coupon;

use App\Enums\Payments\PaymentMethodType;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * A prospective booking to quote a coupon against (POST /coupons/validate, the admin "test eligibility" tool). Carries
 * the customer's choices only; the discount is always computed by the server (Plan I12).
 */
final readonly class CouponQuoteData
{
    public function __construct(
        public ?string $code,
        public int $serviceCategoryId,
        public int $fareMinor,
        public ?PaymentMethodType $paymentMethod,
        public ?GeoPoint $pickup,
        public ?int $cityId = null,
        public ?int $zoneId = null,
        public ?CarbonImmutable $at = null,
    ) {}
}
