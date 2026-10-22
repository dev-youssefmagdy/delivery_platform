<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    use EnumHelpers;

    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return __("enums.discount_type.{$this->value}");
    }
}
