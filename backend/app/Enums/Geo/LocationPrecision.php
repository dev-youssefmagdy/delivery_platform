<?php

namespace App\Enums\Geo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How trustworthy the address of a geocoder result is. Clients must not present `approximate` results as an
 * exact address (SRS §6.3).
 */
enum LocationPrecision: string implements HasLabel
{
    use EnumHelpers;

    case Exact = 'exact';
    case Street = 'street';
    case Approximate = 'approximate';

    public function label(): string
    {
        return __("enums.location_precision.{$this->value}");
    }
}
