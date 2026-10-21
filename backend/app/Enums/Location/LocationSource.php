<?php

namespace App\Enums\Location;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum LocationSource: string implements HasLabel
{
    use EnumHelpers;

    case Gps = 'gps';
    case Fused = 'fused';
    case Network = 'network';

    public function label(): string
    {
        return __("enums.location_source.{$this->value}");
    }
}
