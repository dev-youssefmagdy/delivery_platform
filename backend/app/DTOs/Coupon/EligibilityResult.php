<?php

namespace App\DTOs\Coupon;

use App\DTOs\Finance\DiscountFundingSplit;
use App\Enums\Coupon\CouponReason;
use App\Support\Money\Money;

/**
 * The decision of CouponEligibilityService: eligible or not, why (explanation code), and when eligible the discount
 * and who funds it.
 */
final readonly class EligibilityResult
{
    public function __construct(
        public CouponReason $reason,
        public Money $discount,
        public ?DiscountFundingSplit $funding = null,
    ) {}

    public static function rejected(CouponReason $reason, Money $zero): self
    {
        return new self($reason, $zero);
    }

    public function isEligible(): bool
    {
        return $this->reason->isEligible();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eligible' => $this->isEligible(),
            'reason_code' => $this->reason->value,
            'discount_minor' => $this->discount->amount,
            'currency' => $this->discount->currency->code,
        ];
    }
}
