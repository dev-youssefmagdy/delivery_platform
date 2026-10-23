<?php

namespace App\Enums\Lead;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lead workflow: new -> contacted -> qualified -> converted, with rejected / spam as dead ends that can be reopened (back to new).
 * Rejecting, marking spam and reopening need a reason (audit).
 */
enum LeadStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Rejected = 'rejected';
    case Spam = 'spam';

    public function label(): string
    {
        return __("leads.enums.status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::New => BadgeColor::Info,
            self::Contacted => BadgeColor::Warning,
            self::Qualified => BadgeColor::Primary,
            self::Converted => BadgeColor::Success,
            self::Rejected, self::Spam => BadgeColor::Neutral,
        };
    }

    /**
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::New => [self::Contacted, self::Qualified, self::Rejected, self::Spam],
            self::Contacted => [self::Qualified, self::Converted, self::Rejected, self::Spam],
            self::Qualified => [self::Converted, self::Rejected],
            self::Converted => [],
            self::Rejected, self::Spam => [self::New],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }

    public function needsReason(): bool
    {
        return in_array($this, [self::Rejected, self::Spam, self::New], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Contacted, self::Qualified], true);
    }
}
