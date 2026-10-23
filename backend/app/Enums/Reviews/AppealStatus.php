<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum AppealStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Upheld = 'upheld';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("reputation.enums.appeal_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Warning,
            self::Upheld => BadgeColor::Success,
            self::Rejected => BadgeColor::Neutral,
        };
    }
}
