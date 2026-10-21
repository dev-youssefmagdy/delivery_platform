<?php

namespace App\Enums\Pricing;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How an attribute surcharge of a fare policy applies to the booking's `service_details`.
 */
enum SurchargeMode: string implements HasLabel
{
    use EnumHelpers;

    /** Added once when the field is answered "yes" / true / non-empty, or equals the configured value. */
    case Fixed = 'fixed';

    /** Added per unit: amount x the (integer) answer, for example helpers or extra bags. */
    case PerUnit = 'per_unit';

    public function label(): string
    {
        return __("enums.surcharge_mode.{$this->value}");
    }
}
