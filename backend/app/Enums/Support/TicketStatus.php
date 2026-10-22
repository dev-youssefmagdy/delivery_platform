<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingUser = 'waiting_user';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __("enums.ticket_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Info,
            self::InProgress => BadgeColor::Primary,
            self::WaitingUser => BadgeColor::Warning,
            self::Escalated => BadgeColor::Danger,
            self::Resolved => BadgeColor::Success,
            self::Closed => BadgeColor::Neutral,
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::Open, self::InProgress, self::WaitingUser, self::Escalated];
    }
}
