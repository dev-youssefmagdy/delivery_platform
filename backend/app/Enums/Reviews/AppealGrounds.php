<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum AppealGrounds: string implements HasLabel
{
    use EnumHelpers;

    case HiddenReview = 'hidden_review';
    case UnfairRating = 'unfair_rating';

    public function label(): string
    {
        return __("reputation.enums.appeal_grounds.{$this->value}");
    }
}
