<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Which app audience sees an app banner.
 */
enum BannerAudience: string implements HasLabel
{
    use EnumHelpers;

    case All = 'all';
    case Customer = 'customer';
    case Provider = 'provider';

    public function label(): string
    {
        return __("enums.banner_audience.{$this->value}");
    }
}
