<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum CouponAttemptContext: string implements HasLabel
{
    use EnumHelpers;

    case Validate = 'validate';
    case Booking = 'booking';
    case Automatic = 'automatic';
    case AdminTest = 'admin_test';

    public function label(): string
    {
        return __("enums.coupon_attempt_context.{$this->value}");
    }
}
