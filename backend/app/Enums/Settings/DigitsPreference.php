<?php

namespace App\Enums\Settings;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Numeral style shown to users (SRS §3): follow the language, or force Western / Arabic-Indic digits.
 */
enum DigitsPreference: string implements HasLabel
{
    use EnumHelpers;

    case Auto = 'auto';
    case Western = 'western';
    case ArabicIndic = 'arabic_indic';

    public function label(): string
    {
        return __("enums.digits_preference.{$this->value}");
    }
}
