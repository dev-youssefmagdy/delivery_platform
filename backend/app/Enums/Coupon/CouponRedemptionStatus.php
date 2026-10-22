<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum CouponRedemptionStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Committed = 'committed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __("enums.coupon_redemption_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return $this === self::Committed ? BadgeColor::Success : BadgeColor::Warning;
    }
}
