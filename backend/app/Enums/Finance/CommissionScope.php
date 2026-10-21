<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The dimensions a commission rule can be scoped by. Each matched dimension adds its weight to the rule's
 * specificity; a campaign beats a provider tier beats a zone beats a service (SRS §12.4).
 */
enum CommissionScope: string implements HasLabel
{
    use EnumHelpers;

    case ServiceCategory = 'service_category';
    case Zone = 'zone';
    case ProviderTier = 'provider_tier';
    case Campaign = 'campaign';

    public function weight(): int
    {
        return match ($this) {
            self::ServiceCategory => 1,
            self::Zone => 2,
            self::ProviderTier => 4,
            self::Campaign => 8,
        };
    }

    public function label(): string
    {
        return __("enums.commission_scope.{$this->value}");
    }
}
