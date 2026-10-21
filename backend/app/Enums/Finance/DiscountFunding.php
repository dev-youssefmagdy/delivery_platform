<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Who pays for a customer discount (SRS §9.2). The coupon engine (P3.3) chooses; the breakdown only splits.
 */
enum DiscountFunding: string implements HasLabel
{
    use EnumHelpers;

    case Platform = 'platform';
    case Provider = 'provider';
    case Partner = 'partner';

    public function label(): string
    {
        return __("enums.discount_funding.{$this->value}");
    }
}
