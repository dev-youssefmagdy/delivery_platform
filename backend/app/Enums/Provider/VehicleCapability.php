<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Special capabilities a vehicle can declare (SRS §7.1 step 5). Stored as a JSON list on the vehicle.
 */
enum VehicleCapability: string implements HasLabel
{
    use EnumHelpers;

    case AirConditioning = 'air_conditioning';
    case ChildSeat = 'child_seat';
    case WheelchairAccessible = 'wheelchair_accessible';
    case PetsAllowed = 'pets_allowed';
    case LargeLuggage = 'large_luggage';
    case CoveredCargo = 'covered_cargo';
    case TailLift = 'tail_lift';
    case Refrigerated = 'refrigerated';
    case MovingEquipment = 'moving_equipment';

    public function label(): string
    {
        return __("enums.vehicle_capability.{$this->value}");
    }
}
