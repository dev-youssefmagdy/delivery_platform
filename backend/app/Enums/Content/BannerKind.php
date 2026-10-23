<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * A promotional banner (image) or a text announcement strip.
 */
enum BannerKind: string implements HasLabel
{
    use EnumHelpers;

    case Banner = 'banner';
    case Announcement = 'announcement';

    public function label(): string
    {
        return __("enums.banner_kind.{$this->value}");
    }
}
