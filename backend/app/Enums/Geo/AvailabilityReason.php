<?php

namespace App\Enums\Geo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a service category is not bookable at a position/time.
 */
enum AvailabilityReason: string implements HasLabel
{
    use EnumHelpers;

    case Available = 'available';
    case NotInZone = 'not_in_zone';
    case ZoneClosed = 'zone_closed';
    case CategoryClosed = 'category_closed';
    case OverrideClosed = 'override_closed';

    public function label(): string
    {
        return __("enums.availability_reason.{$this->value}");
    }
}
