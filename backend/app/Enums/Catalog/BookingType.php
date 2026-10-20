<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Booking types a service category can allow (SRS §5.2).
 */
enum BookingType: string implements HasLabel
{
    use EnumHelpers;

    case OnDemand = 'on_demand';
    case Scheduled = 'scheduled';
    case Intercity = 'intercity';
    case Parcel = 'parcel';
    case Freight = 'freight';
    case Hourly = 'hourly';

    public function label(): string
    {
        return __("enums.booking_type.{$this->value}");
    }
}
