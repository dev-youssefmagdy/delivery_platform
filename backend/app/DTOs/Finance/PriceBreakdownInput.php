<?php

namespace App\DTOs\Finance;

use App\Support\Money\Money;

/**
 * What PriceBreakdownCalculator is given. Never a total: only the agreed parts, each decided by the server (Plan I12).
 * The coupon engine (P3.3) fills `discount` and `funding`; tips arrive with payments (P3.2).
 */
final readonly class PriceBreakdownInput
{
    public function __construct(
        public Money $agreedFare,
        public Money $addons,
        public Money $discount,
        public DiscountFundingSplit $funding,
        public Money $customerFee,
        public int $taxRateBp,
        public Money $tip,
        public CommissionTerms $commission,
    ) {}
}
