<?php

namespace App\Enums\Risk;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum RiskSubjectType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';

    public function label(): string
    {
        return __("reputation.enums.subject.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Customer => BadgeColor::Info,
            self::Provider => BadgeColor::Primary,
        };
    }
}
