<?php

namespace App\DTOs\Coupon;

use App\Models\Coupon;

/**
 * The answer to a quote: the coupon (when found), the eligibility decision and a per-funder breakdown of the discount.
 */
final readonly class CouponQuote
{
    public function __construct(
        public ?Coupon $coupon,
        public EligibilityResult $result,
        public int $platformMinor = 0,
        public int $providerMinor = 0,
        public int $partnerMinor = 0,
    ) {}
}
