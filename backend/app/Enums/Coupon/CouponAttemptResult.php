<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum CouponAttemptResult: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Eligible = 'eligible';
    case Rejected = 'rejected';
    case Reserved = 'reserved';
    case ReservationFailed = 'reservation_failed';

    public function label(): string
    {
        return __("enums.coupon_attempt_result.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Eligible => BadgeColor::Info,
            self::Reserved => BadgeColor::Success,
            self::Rejected, self::ReservationFailed => BadgeColor::Danger,
        };
    }
}
