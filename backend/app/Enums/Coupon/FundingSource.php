<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Who pays a coupon's discount (SRS §11.1). `split` carries explicit basis points in the coupon's funding split.
 */
enum FundingSource: string implements HasLabel
{
    use EnumHelpers;

    case Platform = 'platform';
    case Provider = 'provider';
    case Partner = 'partner';
    case Split = 'split';

    public function label(): string
    {
        return __("enums.funding_source.{$this->value}");
    }
}
