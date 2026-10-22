<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * State of a booking adjustment request: pending until the other party decides; only `approved` enters the price.
 */
enum BookingAdjustmentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
    case Voided = 'voided';

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    public function label(): string
    {
        return __("enums.booking_adjustment_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Withdrawn, self::Expired, self::Voided => BadgeColor::Neutral,
        };
    }
}
