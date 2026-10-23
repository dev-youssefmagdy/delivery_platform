<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * State of a granted credit: active (granted), taken back after expiry, or reversed by a person.
 */
enum CreditStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Granted = 'granted';
    case Expired = 'expired';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __("growth.enums.credit_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Granted => BadgeColor::Success,
            self::Expired => BadgeColor::Neutral,
            self::Reversed => BadgeColor::Warning,
        };
    }

}
