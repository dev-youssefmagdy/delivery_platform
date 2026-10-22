<?php

namespace App\DTOs\Coupon;

use App\DTOs\Finance\DiscountFundingSplit;
use App\Support\Money\Money;

/**
 * The discount a booking's coupon reservation grants on a fare, and who funds it: the input of PriceBreakdownCalculator.
 */
final readonly class CouponDiscount
{
    public function __construct(public Money $amount, public DiscountFundingSplit $funding, public int $reservationId) {}
}
