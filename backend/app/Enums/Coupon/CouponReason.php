<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The explanation code returned with every eligibility decision (SRS §11.2: "return the calculated amount and an
 * explanation code"). Stable strings: apps map them to copy, `coupon_attempts.reason_code` stores them.
 */
enum CouponReason: string implements HasLabel
{
    use EnumHelpers;

    case Eligible = 'eligible';
    case NotFound = 'not_found';
    case NotActive = 'not_active';
    case NotStarted = 'not_started';
    case Expired = 'expired';
    case Exhausted = 'exhausted';
    case CategoryNotEligible = 'category_not_eligible';
    case ServiceFamilyNotCovered = 'service_family_not_covered';
    case ServiceCategoryNotCovered = 'service_category_not_covered';
    case ServiceExcluded = 'service_excluded';
    case VehicleNotCovered = 'vehicle_not_covered';
    case CityNotCovered = 'city_not_covered';
    case ZoneNotCovered = 'zone_not_covered';
    case PaymentMethodNotCovered = 'payment_method_not_covered';
    case NewCustomersOnly = 'new_customers_only';
    case ReturningCustomersOnly = 'returning_customers_only';
    case OutsideSchedule = 'outside_schedule';
    case BelowMinimumFare = 'below_minimum_fare';
    case CustomerLimitReached = 'customer_limit_reached';
    case GlobalLimitReached = 'global_limit_reached';
    case BudgetExhausted = 'budget_exhausted';
    case StackingNotAllowed = 'stacking_not_allowed';
    case CurrencyMismatch = 'currency_mismatch';
    case NoDiscount = 'no_discount';

    public function label(): string
    {
        return __("enums.coupon_reason.{$this->value}");
    }

    public function isEligible(): bool
    {
        return $this === self::Eligible;
    }
}
