<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum ModerationActionType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Hide = 'hide';
    case Restore = 'restore';
    case Review = 'review';

    public function label(): string
    {
        return __("reputation.enums.moderation_action.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Hide => BadgeColor::Danger,
            self::Restore => BadgeColor::Success,
            self::Review => BadgeColor::Warning,
        };
    }
}
