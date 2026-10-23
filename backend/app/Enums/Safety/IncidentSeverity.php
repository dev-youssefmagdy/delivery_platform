<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum IncidentSeverity: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return __("safety.enums.severity.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Low => BadgeColor::Neutral,
            self::Medium => BadgeColor::Info,
            self::High => BadgeColor::Warning,
            self::Critical => BadgeColor::Danger,
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 1,
            self::High => 2,
            self::Critical => 3,
        };
    }

    /**
     * Queue priority a severity starts with (an SOS is always urgent).
     */
    public function defaultPriority(): IncidentPriority
    {
        return match ($this) {
            self::Critical => IncidentPriority::Urgent,
            self::High => IncidentPriority::High,
            default => IncidentPriority::Normal,
        };
    }
}
