<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponContext;
use App\DTOs\Coupon\CouponDefinition;
use App\DTOs\Coupon\EligibilityResult;
use App\Enums\Coupon\CouponReason;
use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\CouponSegment;
use App\Enums\Coupon\CouponStatus;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Decides whether a coupon applies (SRS §11.2 step 2) and what it is worth. PURE: no database, no clock (the time of the
 * trip is part of the context), so every rule is unit-testable. The first failing rule wins, in this order: status and
 * window, currency, category flag, scope (family, category, vehicle, city, zone), exclusions, payment method, segment,
 * stacking, schedule, minimum fare, zero discount, per-customer limit, global limit, budget.
 *
 * The limit and budget checks here read the counters of the definition they are given; CouponReservationService repeats
 * them inside the coupon row lock, which is what makes concurrent use safe.
 */
final class CouponEligibilityService
{
    public function evaluate(CouponDefinition $coupon, CouponContext $context): EligibilityResult
    {
        $zero = Money::zero($context->fare->currency);
        $rejected = static fn (CouponReason $reason): EligibilityResult => EligibilityResult::rejected($reason, $zero);

        $state = $this->statusReason($coupon, $context);

        if ($state !== null) {
            return $rejected($state);
        }

        if ($coupon->currency !== $context->fare->currency->code) {
            return $rejected(CouponReason::CurrencyMismatch);
        }

        if (! $context->categoryAllowsCoupons) {
            return $rejected(CouponReason::CategoryNotEligible);
        }

        $scope = $this->scopeReason($coupon, $context);

        if ($scope !== null) {
            return $rejected($scope);
        }

        if ($coupon->paymentMethods !== [] && ($context->paymentMethod === null || ! in_array($context->paymentMethod, $coupon->paymentMethods, true))) {
            return $rejected(CouponReason::PaymentMethodNotCovered);
        }

        $segment = $this->segmentReason($coupon, $context);

        if ($segment !== null) {
            return $rejected($segment);
        }

        if ($context->hasOtherDiscount && ! $coupon->stackable) {
            return $rejected(CouponReason::StackingNotAllowed);
        }

        if ($coupon->schedule !== [] && ! $this->withinSchedule($coupon, $context)) {
            return $rejected(CouponReason::OutsideSchedule);
        }

        if ($coupon->terms->minFareMinor !== null && $context->fare->amount < $coupon->terms->minFareMinor) {
            return $rejected(CouponReason::BelowMinimumFare);
        }

        $discount = $coupon->terms->discountFor($context->fare);

        if (! $discount->isPositive()) {
            return $rejected(CouponReason::NoDiscount);
        }

        if ($coupon->perCustomerLimit !== null && $context->userId !== null && $context->customerUses >= $coupon->perCustomerLimit) {
            return $rejected(CouponReason::CustomerLimitReached);
        }

        if ($coupon->remainingUses() === 0) {
            return $rejected(CouponReason::GlobalLimitReached);
        }

        $remaining = $coupon->remainingBudgetMinor();

        if ($remaining !== null && $remaining < $discount->amount) {
            return $rejected(CouponReason::BudgetExhausted);
        }

        return new EligibilityResult(CouponReason::Eligible, $discount, $coupon->terms->funding());
    }

    private function statusReason(CouponDefinition $coupon, CouponContext $context): ?CouponReason
    {
        $reason = match ($coupon->status) {
            CouponStatus::Active => null,
            CouponStatus::Scheduled => CouponReason::NotStarted,
            CouponStatus::Expired => CouponReason::Expired,
            CouponStatus::Exhausted => CouponReason::Exhausted,
            default => CouponReason::NotActive,
        };

        if ($reason !== null) {
            return $reason;
        }

        if ($coupon->startsAt !== null && $coupon->startsAt->greaterThan($context->at)) {
            return CouponReason::NotStarted;
        }

        if ($coupon->endsAt !== null && $coupon->endsAt->lessThanOrEqualTo($context->at)) {
            return CouponReason::Expired;
        }

        return null;
    }

    private function scopeReason(CouponDefinition $coupon, CouponContext $context): ?CouponReason
    {
        $family = $coupon->scope(CouponScopeType::ServiceFamily);
        $category = $coupon->scope(CouponScopeType::ServiceCategory);
        $vehicle = $coupon->scope(CouponScopeType::VehicleCategory);
        $city = $coupon->scope(CouponScopeType::City);
        $zone = $coupon->scope(CouponScopeType::Zone);

        return match (true) {
            $family !== [] && ($context->serviceFamilyId === null || ! in_array($context->serviceFamilyId, $family, true)) => CouponReason::ServiceFamilyNotCovered,
            $category !== [] && ($context->serviceCategoryId === null || ! in_array($context->serviceCategoryId, $category, true)) => CouponReason::ServiceCategoryNotCovered,
            $vehicle !== [] && array_intersect($vehicle, $context->vehicleCategoryIds) === [] => CouponReason::VehicleNotCovered,
            $city !== [] && ($context->cityId === null || ! in_array($context->cityId, $city, true)) => CouponReason::CityNotCovered,
            $zone !== [] && ($context->zoneId === null || ! in_array($context->zoneId, $zone, true)) => CouponReason::ZoneNotCovered,
            $this->excluded($coupon, $context) => CouponReason::ServiceExcluded,
            default => null,
        };
    }

    private function excluded(CouponDefinition $coupon, CouponContext $context): bool
    {
        $categories = $coupon->exclusions['service_category_ids'] ?? [];
        $vehicles = $coupon->exclusions['vehicle_category_ids'] ?? [];

        if ($context->serviceCategoryId !== null && in_array($context->serviceCategoryId, $categories, true)) {
            return true;
        }

        return $vehicles !== [] && $context->vehicleCategoryIds !== [] && array_diff($context->vehicleCategoryIds, $vehicles) === [];
    }

    private function segmentReason(CouponDefinition $coupon, CouponContext $context): ?CouponReason
    {
        $segments = $coupon->segments;

        if ($segments === [] || count($segments) > 1) {
            return null;
        }

        return match (true) {
            $segments[0] === CouponSegment::NewCustomers && ! $context->isNewCustomer => CouponReason::NewCustomersOnly,
            $segments[0] === CouponSegment::ReturningCustomers && $context->isNewCustomer => CouponReason::ReturningCustomersOnly,
            default => null,
        };
    }

    /**
     * The time of the trip, in the coupon's timezone, falls in one of the allowed weekly windows (an overnight window
     * such as 22:00-02:00 belongs to the weekday it starts on).
     */
    private function withinSchedule(CouponDefinition $coupon, CouponContext $context): bool
    {
        $local = CarbonImmutable::instance($context->at)->setTimezone($coupon->timezone);
        $day = $local->dayOfWeekIso;
        $previous = $day === 1 ? 7 : $day - 1;
        $time = $local->format('H:i');

        foreach ($coupon->schedule as $window) {
            $from = $window['from'];
            $to = $window['to'];
            $overnight = $to <= $from;

            if ($window['day'] === $day && ($overnight ? $time >= $from : ($time >= $from && $time < $to))) {
                return true;
            }

            if ($overnight && $window['day'] === $previous && $time < $to) {
                return true;
            }
        }

        return false;
    }
}
