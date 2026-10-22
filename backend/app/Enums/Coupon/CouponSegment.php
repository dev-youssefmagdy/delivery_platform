<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Customer segment a coupon is limited to: a customer is "new" until they have a completed booking.
 */
enum CouponSegment: string implements HasLabel
{
    use EnumHelpers;

    case NewCustomers = 'new';
    case ReturningCustomers = 'returning';

    public function label(): string
    {
        return __("enums.coupon_segment.{$this->value}");
    }
}
