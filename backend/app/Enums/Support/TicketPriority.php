<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;

enum TicketPriority: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __("enums.ticket_priority.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Low => BadgeColor::Neutral,
            self::Normal => BadgeColor::Info,
            self::High => BadgeColor::Warning,
            self::Urgent => BadgeColor::Danger,
        };
    }

    /**
     * Minutes until the first response is due (SLA), from `config('support.sla_minutes')`.
     */
    public function slaMinutes(): int
    {
        return (int) config("support.sla_minutes.{$this->value}", 1440);
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Normal => 1,
            self::High => 2,
            self::Urgent => 3,
        };
    }
}
