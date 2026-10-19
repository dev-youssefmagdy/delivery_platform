<?php

namespace App\Enums\Policy;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Kinds of legal/policy documents with versioned, consent-tracked content (SRS §13.3, §16.15).
 */
enum PolicyType: string implements HasLabel
{
    use EnumHelpers;

    case Terms = 'terms';
    case Privacy = 'privacy';
    case DriverTerms = 'driver_terms';
    case CouponRules = 'coupon_rules';
    case RestrictedGoods = 'restricted_goods';
    case Cancellation = 'cancellation';

    public function label(): string
    {
        return __("enums.policy_type.{$this->value}");
    }
}
