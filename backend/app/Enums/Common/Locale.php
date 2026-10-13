<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Supported application locales. Arabic is the default and fallback (SRS §3, §18).
 */
enum Locale: string implements HasLabel
{
    use EnumHelpers;

    case Arabic = 'ar';
    case English = 'en';

    public static function default(): self
    {
        return self::Arabic;
    }

    public function label(): string
    {
        return __("enums.locale.{$this->value}");
    }

    public function direction(): string
    {
        return $this === self::Arabic ? 'rtl' : 'ltr';
    }

    public function isRtl(): bool
    {
        return $this->direction() === 'rtl';
    }
}
