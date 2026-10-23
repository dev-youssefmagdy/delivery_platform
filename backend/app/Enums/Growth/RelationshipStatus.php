<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Referral relationship: pending until the milestone, under review when a weak fraud signal holds the reward, rewarded or rejected.
 */
enum RelationshipStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Rewarded = 'rewarded';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("growth.enums.relationship_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Info,
            self::UnderReview => BadgeColor::Warning,
            self::Rewarded => BadgeColor::Success,
            self::Rejected => BadgeColor::Neutral,
        };
    }

}
