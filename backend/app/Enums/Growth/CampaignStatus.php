<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of referral and incentive campaigns. Pausing and archiving need a reason (audit); only active campaigns earn.
 */
enum CampaignStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return __("growth.enums.campaign_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Active => BadgeColor::Success,
            self::Paused => BadgeColor::Warning,
            self::Archived => BadgeColor::Neutral,
        };
    }

    /**
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Archived],
            self::Active => [self::Paused, self::Archived],
            self::Paused => [self::Active, self::Archived],
            self::Archived => [],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }

    public function needsReason(): bool
    {
        return $this === self::Paused || $this === self::Archived;
    }

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Paused;
    }
}
