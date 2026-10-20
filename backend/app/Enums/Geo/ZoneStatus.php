<?php

namespace App\Enums\Geo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of a service zone. Only `active` zones in an active city serve bookings.
 */
enum ZoneStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("enums.zone_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Active => BadgeColor::Success,
            self::Suspended => BadgeColor::Warning,
        };
    }
}
