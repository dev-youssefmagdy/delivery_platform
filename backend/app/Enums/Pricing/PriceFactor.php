<?php

namespace App\Enums\Pricing;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The components a reference price is made of; each one is returned to the app with its amount so the customer can
 * see what affects the guidance (SRS §6.4).
 */
enum PriceFactor: string implements HasLabel
{
    use EnumHelpers;

    case Base = 'base';
    case Distance = 'distance';
    case Duration = 'duration';
    case Stops = 'stops';
    case Surcharge = 'surcharge';
    case MinimumFare = 'minimum_fare';

    public function label(): string
    {
        return __("enums.price_factor.{$this->value}");
    }
}
