<?php

namespace App\Enums\Risk;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum RiskSignalStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Escalated = 'escalated';
    case Dismissed = 'dismissed';
    case Actioned = 'actioned';

    public function label(): string
    {
        return __("reputation.enums.status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Warning,
            self::Escalated => BadgeColor::Danger,
            self::Dismissed => BadgeColor::Neutral,
            self::Actioned => BadgeColor::Success,
        };
    }

    public function isActive(): bool
    {
        return $this === self::Open || $this === self::Escalated;
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [self::Open->value, self::Escalated->value];
    }
}
