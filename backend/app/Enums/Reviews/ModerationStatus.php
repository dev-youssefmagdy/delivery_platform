<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Whether a rating counts: only `visible` ratings enter the public average (SRS §15).
 */
enum ModerationStatus: string implements HasLabel
{
    use EnumHelpers;

    case Visible = 'visible';
    case Hidden = 'hidden';
    case UnderReview = 'under_review';

    public function label(): string
    {
        return __("enums.moderation_status.{$this->value}");
    }
}
