<?php

namespace App\DTOs\Coupon;

use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\CouponSegment;
use App\Enums\Coupon\CouponStatus;
use App\Enums\Payments\PaymentMethodType;
use Carbon\CarbonInterface;

/**
 * Everything the eligibility rules need to know about a coupon, detached from Eloquent so CouponEligibilityService
 * stays pure. `terms` are the financial terms of the version being evaluated (the current one for a quote; the
 * reservation's own for a booking that already holds one). Counters are read inside the coupon row lock when a
 * reservation is made.
 */
final readonly class CouponDefinition
{
    /**
     * @param array<string, list<int>> $scopes CouponScopeType value => ids (missing / empty = unrestricted)
     * @param array<string, list<int>> $exclusions `service_category_ids`, `vehicle_category_ids`
     * @param list<CouponSegment> $segments
     * @param list<PaymentMethodType> $paymentMethods
     * @param list<array{day: int, from: string, to: string}> $schedule ISO weekday 1..7, local HH:MM window in `timezone`
     */
    public function __construct(
        public int $id,
        public ?string $code,
        public CouponStatus $status,
        public string $currency,
        public CouponTerms $terms,
        public ?CarbonInterface $startsAt,
        public ?CarbonInterface $endsAt,
        public string $timezone,
        public array $scopes,
        public array $exclusions,
        public array $segments,
        public array $paymentMethods,
        public array $schedule,
        public ?int $perCustomerLimit,
        public ?int $globalLimit,
        public ?int $budgetMinor,
        public int $usedCount,
        public int $reservedCount,
        public int $spentMinor,
        public int $reservedMinor,
        public bool $stackable,
    ) {}

    /**
     * @return list<int>
     */
    public function scope(CouponScopeType $type): array
    {
        return $this->scopes[$type->value] ?? [];
    }

    public function remainingBudgetMinor(): ?int
    {
        return $this->budgetMinor === null ? null : max(0, $this->budgetMinor - $this->spentMinor - $this->reservedMinor);
    }

    public function remainingUses(): ?int
    {
        return $this->globalLimit === null ? null : max(0, $this->globalLimit - $this->usedCount - $this->reservedCount);
    }
}
