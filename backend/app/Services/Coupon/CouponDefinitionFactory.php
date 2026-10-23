<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponDefinition;
use App\DTOs\Coupon\CouponTerms;
use App\Enums\Coupon\CouponSegment;
use App\Enums\Payments\PaymentMethodType;
use App\Models\Coupon;
use App\Models\CouponScope;

/**
 * Builds the pure CouponDefinition (and CouponTerms) from a coupon row. The scope rows must be loaded (`scopes`).
 */
final class CouponDefinitionFactory
{
    public function termsOf(Coupon $coupon): CouponTerms
    {
        $split = (array) $coupon->funding_split;
        [$platform, $provider, $partner] = CouponTerms::splitFor(
            $coupon->funding_source,
            (int) ($split['platform_bp'] ?? 0),
            (int) ($split['provider_bp'] ?? 0),
            (int) ($split['partner_bp'] ?? 0),
        );

        return new CouponTerms(
            type: $coupon->discount_type,
            value: $coupon->discount_value,
            maxDiscountMinor: $coupon->max_discount_minor,
            minFareMinor: $coupon->min_fare_minor,
            fundingSource: $coupon->funding_source,
            platformBp: $platform,
            providerBp: $provider,
            partnerBp: $partner,
            restoreOnRefund: $coupon->restore_on_refund,
        );
    }

    /**
     * @param CouponTerms|null $terms the terms to evaluate under (a reservation's own version); the coupon's current terms by default
     */
    public function make(Coupon $coupon, ?CouponTerms $terms = null): CouponDefinition
    {
        $scopes = [];

        foreach ($coupon->scopes as $scope) {
            /** @var CouponScope $scope */
            $scopes[$scope->type->value][] = $scope->ref_id;
        }

        $exclusions = [];

        foreach (['service_category_ids', 'vehicle_category_ids'] as $key) {
            $exclusions[$key] = array_values(array_map('intval', (array) ($coupon->exclusions[$key] ?? [])));
        }

        return new CouponDefinition(
            id: $coupon->id,
            code: $coupon->code,
            status: $coupon->status,
            currency: $coupon->currency,
            terms: $terms ?? $this->termsOf($coupon),
            startsAt: $coupon->starts_at,
            endsAt: $coupon->ends_at,
            timezone: $coupon->timezone,
            scopes: $scopes,
            exclusions: $exclusions,
            segments: array_values(array_filter(array_map(static fn (mixed $value): ?CouponSegment => CouponSegment::tryFrom((string) $value), (array) $coupon->segments))),
            paymentMethods: array_values(array_filter(array_map(static fn (mixed $value): ?PaymentMethodType => PaymentMethodType::tryFrom((string) $value), (array) $coupon->payment_methods))),
            schedule: array_values(array_map(static fn (array $window): array => ['day' => (int) $window['day'], 'from' => (string) $window['from'], 'to' => (string) $window['to']], (array) $coupon->schedule)),
            perCustomerLimit: $coupon->per_customer_limit,
            globalLimit: $coupon->global_limit,
            budgetMinor: $coupon->budget_minor,
            usedCount: $coupon->used_count,
            reservedCount: $coupon->reserved_count,
            spentMinor: $coupon->spent_minor,
            reservedMinor: $coupon->reserved_minor,
            stackable: $coupon->stackable,
        );
    }
}
