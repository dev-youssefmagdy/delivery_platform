<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum RatingReportStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Actioned = 'actioned';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return __("reputation.enums.report_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Warning,
            self::Actioned => BadgeColor::Success,
            self::Dismissed => BadgeColor::Neutral,
        };
    }
}
