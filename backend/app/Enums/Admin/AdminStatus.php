<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum AdminStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("enums.admin_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Suspended => BadgeColor::Danger,
        };
    }
}
