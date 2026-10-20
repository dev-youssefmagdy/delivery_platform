<?php

namespace App\Enums\Geo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Health of the maps adapter as reported to the integration health page.
 */
enum MapsProviderStatus: string implements HasLabel
{
    use EnumHelpers;

    case Ok = 'ok';
    case NotConfigured = 'not_configured';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return __("enums.maps_provider_status.{$this->value}");
    }
}
