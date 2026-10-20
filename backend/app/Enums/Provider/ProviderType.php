<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What kind of provider an applicant is (SRS §7.1 step 3). Company / fleet data stays modelled even though fleet
 * administration is phased, so the type never blocks the data model.
 */
enum ProviderType: string implements HasLabel
{
    use EnumHelpers;

    case IndividualDriver = 'individual_driver';
    case Courier = 'courier';
    case MovingCrew = 'moving_crew';
    case CommercialTransport = 'commercial_transport';
    case CompanyFleet = 'company_fleet';

    public function label(): string
    {
        return __("enums.provider_type.{$this->value}");
    }
}
