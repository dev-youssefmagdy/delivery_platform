<?php

namespace App\Enums\Location;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a location sample was flagged. Flagged samples are stored in the history (never silently dropped) but do not
 * move the provider on the geo index.
 */
enum LocationAnomaly: string implements HasLabel
{
    use EnumHelpers;

    case ImpossibleSpeed = 'impossible_speed';
    case FutureTimestamp = 'future_timestamp';

    public function label(): string
    {
        return __("enums.location_anomaly.{$this->value}");
    }
}
