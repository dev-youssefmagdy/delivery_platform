<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Listing filter: does the item have an English translation or only Arabic?
 */
enum LocaleCompleteness: string implements HasLabel
{
    use EnumHelpers;

    case Complete = 'complete';
    case ArabicOnly = 'arabic_only';

    public function label(): string
    {
        return __("enums.locale_completeness.{$this->value}");
    }
}
