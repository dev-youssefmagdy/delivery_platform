<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Who a referral campaign is for: customers refer customers, providers refer providers.
 */
enum ReferralAudience: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';

    public function label(): string
    {
        return __("growth.enums.audience.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Customer => BadgeColor::Info,
            self::Provider => BadgeColor::Primary,
        };
    }

    public function walletOwnerType(): string
    {
        return $this === self::Customer ? 'customer_wallet' : 'provider_wallet';
    }
}
