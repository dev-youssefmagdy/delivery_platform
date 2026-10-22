<?php

namespace App\Enums\Scope;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The line of an agreed scope that a post-selection adjustment refers to (SRS §9.4): a change of price must name the
 * item of the accepted scope it changes (an extra helper, stairs beyond the agreed floors, an extra stop ...).
 */
enum ScopeItem: string implements HasLabel
{
    use EnumHelpers;

    case Helpers = 'helpers';
    case Stairs = 'stairs';
    case ExtraStops = 'extra_stops';
    case WaitingAllowance = 'waiting_allowance';
    case Tolls = 'tolls';
    case Parking = 'parking';
    case Packing = 'packing';
    case Assembly = 'assembly';
    case LoadingEquipment = 'loading_equipment';

    public function label(): string
    {
        return __("enums.scope_item.{$this->value}");
    }
}
