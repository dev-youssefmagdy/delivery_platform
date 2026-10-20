<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a service family does (SRS §5.1). Clients branch on the kind only for presentation (icons, grouping).
 */
enum ServiceFamilyKind: string implements HasLabel
{
    use EnumHelpers;

    case PassengerCity = 'passenger_city';
    case Intercity = 'intercity';
    case Courier = 'courier';
    case Moving = 'moving';
    case Freight = 'freight';
    case Hourly = 'hourly';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.service_family_kind.{$this->value}");
    }
}
