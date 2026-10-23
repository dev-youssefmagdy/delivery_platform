<?php

namespace App\Enums\System;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum AppPlatform: string implements HasLabel
{
    use EnumHelpers;

    case Android = 'android';
    case Ios = 'ios';

    public function label(): string
    {
        return __("system.enums.platform.{$this->value}");
    }
}
