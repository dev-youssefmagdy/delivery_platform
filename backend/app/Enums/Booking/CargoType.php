<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a courier parcel or freight load is (`service_details.parcel_category` / `service_details.cargo_type`), for the admin
 * bookings filter. Values are the option values of the seeded form schemas.
 */
enum CargoType: string implements HasLabel
{
    use EnumHelpers;

    case General = 'general';
    case Building = 'building';
    case Food = 'food';
    case Machinery = 'machinery';
    case Documents = 'documents';
    case Clothes = 'clothes';
    case Electronics = 'electronics';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.cargo_type.{$this->value}");
    }
}
