<?php

namespace App\Enums\Reviews;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum RatingReportReason: string implements HasLabel
{
    use EnumHelpers;

    case Abusive = 'abusive';
    case Spam = 'spam';
    case Irrelevant = 'irrelevant';
    case FalseInformation = 'false_info';
    case PersonalInformation = 'personal_info';
    case Other = 'other';

    public function label(): string
    {
        return __("reputation.enums.report_reason.{$this->value}");
    }
}
