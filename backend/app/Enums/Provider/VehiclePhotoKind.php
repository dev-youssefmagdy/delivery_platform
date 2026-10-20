<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Which side of the vehicle a photo shows.
 */
enum VehiclePhotoKind: string implements HasLabel
{
    use EnumHelpers;

    case Front = 'front';
    case Back = 'back';
    case Side = 'side';
    case Interior = 'interior';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.vehicle_photo_kind.{$this->value}");
    }
}
