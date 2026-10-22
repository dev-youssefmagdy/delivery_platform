<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * ADR-17: reserved at booking creation, released on cancel / expiry, committed on completion, reversed on a refund
 * when the coupon's policy says so.
 */
enum CouponReservationStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Reserved = 'reserved';
    case Released = 'released';
    case Committed = 'committed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __("enums.coupon_reservation_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Reserved => BadgeColor::Info,
            self::Committed => BadgeColor::Success,
            self::Released => BadgeColor::Neutral,
            self::Reversed => BadgeColor::Warning,
        };
    }

    /**
     * The states that count against a customer's per-customer limit and the global limit.
     *
     * @return list<self>
     */
    public static function counting(): array
    {
        return [self::Reserved, self::Committed];
    }
}
