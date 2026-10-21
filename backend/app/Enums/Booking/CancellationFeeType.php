<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How a cancellation fee is computed: nothing, a fixed amount in minor units, or basis points of the agreed fare.
 */
enum CancellationFeeType: string implements HasLabel
{
    use EnumHelpers;

    case None = 'none';
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return __("enums.cancellation_fee_type.{$this->value}");
    }
}
