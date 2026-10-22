<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Package sizes of the courier form (`service_details.parcel_size`, copied to `bookings.parcel_size` for admin filters).
 */
enum ParcelSize: string implements HasLabel
{
    use EnumHelpers;

    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    public function label(): string
    {
        return __("enums.parcel_size.{$this->value}");
    }
}
