<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Triage order of the safety queue. The SLA is the time until a safety officer acknowledges the case (`safety.sla_minutes`).
 */
enum IncidentPriority: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __("safety.enums.priority.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Normal => BadgeColor::Info,
            self::High => BadgeColor::Warning,
            self::Urgent => BadgeColor::Danger,
        };
    }

    public function slaMinutes(): int
    {
        return (int) config("safety.sla_minutes.{$this->value}", 60);
    }

    public function weight(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::High => 1,
            self::Urgent => 2,
        };
    }
}
