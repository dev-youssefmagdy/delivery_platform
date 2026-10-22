<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The dimensions a coupon can be restricted by (rows of `coupon_scopes`). No row of a type means no restriction.
 */
enum CouponScopeType: string implements HasLabel
{
    use EnumHelpers;

    case ServiceFamily = 'service_family';
    case ServiceCategory = 'service_category';
    case VehicleCategory = 'vehicle_category';
    case City = 'city';
    case Zone = 'zone';

    public function label(): string
    {
        return __("enums.coupon_scope_type.{$this->value}");
    }
}
