<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Store a mobile app is published on.
 */
enum AppPlatform: string implements HasLabel
{
    use EnumHelpers;

    case Android = 'android';
    case Ios = 'ios';
    case Huawei = 'huawei';

    public function label(): string
    {
        return __("enums.app_platform.{$this->value}");
    }
}
