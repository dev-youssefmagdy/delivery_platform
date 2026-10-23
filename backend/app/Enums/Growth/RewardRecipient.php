<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Who a referral reward is for.
 */
enum RewardRecipient: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Referrer = 'referrer';
    case Referee = 'referee';

    public function label(): string
    {
        return __("growth.enums.recipient.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Referrer => BadgeColor::Primary,
            self::Referee => BadgeColor::Info,
        };
    }
}
