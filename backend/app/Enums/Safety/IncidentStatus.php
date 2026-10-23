<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum IncidentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case New = 'new';
    case Acknowledged = 'acknowledged';
    case Investigating = 'investigating';
    case Escalated = 'escalated';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __("safety.enums.status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::New => BadgeColor::Danger,
            self::Acknowledged => BadgeColor::Info,
            self::Investigating => BadgeColor::Primary,
            self::Escalated => BadgeColor::Warning,
            self::Resolved => BadgeColor::Success,
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Resolved;
    }

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::New, self::Acknowledged, self::Investigating, self::Escalated];
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::activeCases());
    }
}
