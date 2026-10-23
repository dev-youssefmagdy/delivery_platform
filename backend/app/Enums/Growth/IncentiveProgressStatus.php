<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Provider progress in an incentive campaign: counting, held for fraud review, blocked by an empty budget, or rejected by a person.
 */
enum IncentiveProgressStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case InProgress = 'in_progress';
    case Held = 'held';
    case Exhausted = 'exhausted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("growth.enums.progress_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::InProgress => BadgeColor::Info,
            self::Held => BadgeColor::Warning,
            self::Exhausted => BadgeColor::Neutral,
            self::Rejected => BadgeColor::Neutral,
        };
    }
}
