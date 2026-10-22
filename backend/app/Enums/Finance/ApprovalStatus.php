<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * State of a maker-checker request (financial adjustments): pending until a different admin decides.
 */
enum ApprovalStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.approval_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Cancelled => BadgeColor::Neutral,
        };
    }
}
