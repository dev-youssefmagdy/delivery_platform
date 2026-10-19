<?php

namespace App\Enums\Demo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum DemoItemType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Standard = 'standard';
    case Premium = 'premium';
    case Bundle = 'bundle';

    public function label(): string
    {
        return __("enums.demo_item_type.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Standard => BadgeColor::Neutral,
            self::Premium => BadgeColor::Primary,
            self::Bundle => BadgeColor::Info,
        };
    }
}
