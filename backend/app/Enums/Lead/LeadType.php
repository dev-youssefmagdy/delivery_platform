<?php

namespace App\Enums\Lead;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum LeadType: string implements HasLabel
{
    use EnumHelpers;

    case ProviderOnboarding = 'provider_onboarding';

    public function label(): string
    {
        return __("leads.enums.type.{$this->value}");
    }
}
