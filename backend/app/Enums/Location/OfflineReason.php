<?php

namespace App\Enums\Location;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a provider stopped being available.
 */
enum OfflineReason: string implements HasLabel
{
    use EnumHelpers;

    case ProviderChoice = 'provider_choice';
    case StaleLocation = 'stale_location';
    case NoLongerEligible = 'no_longer_eligible';
    case AccountChanged = 'account_changed';

    public function label(): string
    {
        return __("enums.offline_reason.{$this->value}");
    }
}
