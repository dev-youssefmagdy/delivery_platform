<?php

namespace App\DTOs\Coupon;

use App\Support\Money\Money;

/**
 * A coupon that passed the pre-checks of booking creation and is about to be reserved inside the booking transaction.
 */
final readonly class CouponPlan
{
    public function __construct(
        public int $couponId,
        public ?string $codeEntered,
        public Money $hold,
        public bool $automatic,
    ) {}
}
