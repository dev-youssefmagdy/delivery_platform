<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum Platform: string implements HasLabel
{
    use EnumHelpers;

    case Android = 'android';
    case Ios = 'ios';
    case Web = 'web';

    public function label(): string
    {
        return __("enums.platform.{$this->value}");
    }
}
