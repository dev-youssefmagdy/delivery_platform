<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponQuote;
use App\DTOs\Coupon\CouponQuoteData;
use App\Enums\Coupon\CouponAttemptContext;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponReason;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Coupon;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Services\Geography\GeoService;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * "What would this coupon do?" for a prospective booking (POST /coupons/validate, the admin test tool): the same eligibility
 * rules as booking creation, without reserving anything. The attempt is logged. Never trusts a client discount.
 */
final class CouponQuoteService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly CouponReservationRepositoryInterface $reservations,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly CouponEligibilityService $eligibility,
        private readonly CouponDefinitionFactory $definitions,
        private readonly CouponContextBuilder $contexts,
        private readonly CouponAttemptRecorder $attempts,
        private readonly GeoService $geo,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * @param Coupon|null $coupon a coupon already chosen (admin test); otherwise the one the code names
     *
     * @throws BusinessRuleViolation `category_unavailable` for an unknown category
     */
    public function quote(?User $customer, CouponQuoteData $data, CouponAttemptContext $attemptContext, ?Coupon $coupon = null): CouponQuote
    {
        $category = $this->categories->find($data->serviceCategoryId);

        if (! $category instanceof ServiceCategory) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        $category->loadMissing('publishedVersion');
        $at = $data->at ?? CarbonImmutable::now();
        $currency = $this->currencies->get($this->settings->string(SettingKey::DefaultCurrency));
        $zero = Money::zero($currency);
        $coupon ??= $data->code === null ? null : $this->coupons->findByCode($data->code);

        if (! $coupon instanceof Coupon) {
            $this->attempts->record($attemptContext, CouponAttemptResult::Rejected, CouponReason::NotFound, null, $data->code, $customer?->id, $category->id);

            return new CouponQuote(null, \App\DTOs\Coupon\EligibilityResult::rejected(CouponReason::NotFound, $zero));
        }

        $coupon->loadMissing('scopes');
        $area = $data->pickup === null ? null : $this->geo->resolve($data->pickup, $at);
        $context = $this->contexts->build(
            $customer?->id,
            $category,
            $category->publishedVersion,
            $data->cityId ?? $area?->city?->id,
            $data->zoneId ?? $area?->zone?->id,
            $data->paymentMethod,
            Money::of($data->fareMinor, $this->currencies->get($coupon->currency)),
            $at,
        );
        $uses = $customer === null ? 0 : $this->reservations->countingForUser($coupon->id, $customer->id);
        $result = $this->eligibility->evaluate($this->definitions->make($coupon), $context->withCustomerUses($uses));

        $this->attempts->record(
            $attemptContext,
            $result->isEligible() ? CouponAttemptResult::Eligible : CouponAttemptResult::Rejected,
            $result->reason,
            $coupon->id,
            $data->code ?? $coupon->code,
            $customer?->id,
            $category->id,
            discountMinor: $result->isEligible() ? $result->discount->amount : null,
        );

        if (! $result->isEligible() || $result->funding === null) {
            return new CouponQuote($coupon, $result);
        }

        [$platform, $provider, $partner] = $result->discount->allocate($result->funding->ratios());

        return new CouponQuote($coupon, $result, $platform->amount, $provider->amount, $partner->amount);
    }
}
