<?php

namespace App\Support\Coupon;

use App\DTOs\Coupon\CouponTerms;
use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\CouponSegment;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Enums\Payments\PaymentMethodType;
use App\Models\City;
use App\Models\Coupon;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\ServiceZone;
use App\Models\VehicleCategory;
use App\Services\Coupon\CouponDefinitionFactory;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonInterface;

/**
 * Human text for a coupon in the current locale: the discount line ("20% off, up to EGP 50"), the funding line and the
 * list of conditions. Used by the admin listing, the preview on the coupon page and the customer API summary. Display only:
 * amounts are never parsed back from it.
 */
final class CouponPresenter
{
    public function __construct(
        private readonly CurrencyRegistry $currencies,
        private readonly MoneyFormatter $formatter,
        private readonly CouponDefinitionFactory $definitions,
    ) {}

    public function discount(CouponTerms $terms, string $currency): string
    {
        $text = $terms->type === DiscountType::Percentage
            ? __('admin.coupons.preview.percent_off', ['percent' => $this->percent($terms->value)])
            : __('admin.coupons.preview.fixed_off', ['amount' => $this->money($terms->value, $currency)]);

        if ($terms->maxDiscountMinor !== null) {
            $text .= ', '.__('admin.coupons.preview.up_to', ['amount' => $this->money($terms->maxDiscountMinor, $currency)]);
        }

        if ($terms->minFareMinor !== null) {
            $text .= ', '.__('admin.coupons.preview.min_fare', ['amount' => $this->money($terms->minFareMinor, $currency)]);
        }

        return $text;
    }

    public function funding(CouponTerms $terms): string
    {
        if ($terms->fundingSource !== FundingSource::Split) {
            return $terms->fundingSource->label();
        }

        return __('admin.coupons.preview.split', [
            'platform' => $this->percent($terms->platformBp),
            'provider' => $this->percent($terms->providerBp),
            'partner' => $this->percent($terms->partnerBp),
        ]);
    }

    /**
     * The conditions that decide who can use the coupon, one line each. The scope rows must be loaded (`scopes`).
     *
     * @return list<string>
     */
    public function conditions(Coupon $coupon): array
    {
        $definition = $this->definitions->make($coupon);
        $lines = [];

        foreach ([
            CouponScopeType::ServiceFamily->value => [ServiceFamily::class, 'admin.coupons.preview.families'],
            CouponScopeType::ServiceCategory->value => [ServiceCategory::class, 'admin.coupons.preview.categories'],
            CouponScopeType::VehicleCategory->value => [VehicleCategory::class, 'admin.coupons.preview.vehicles'],
            CouponScopeType::City->value => [City::class, 'admin.coupons.preview.cities'],
            CouponScopeType::Zone->value => [ServiceZone::class, 'admin.coupons.preview.zones'],
        ] as $type => [$model, $key]) {
            $ids = $definition->scopes[$type] ?? [];

            if ($ids !== []) {
                $lines[] = __($key, ['names' => $this->names($model, $ids)]);
            }
        }

        foreach ([['service_category_ids', ServiceCategory::class], ['vehicle_category_ids', VehicleCategory::class]] as [$key, $model]) {
            if (($definition->exclusions[$key] ?? []) !== []) {
                $lines[] = __('admin.coupons.preview.excluded', ['names' => $this->names($model, $definition->exclusions[$key])]);
            }
        }

        if ($definition->paymentMethods !== []) {
            $lines[] = __('admin.coupons.preview.payment_methods', ['methods' => implode(', ', array_map(static fn (PaymentMethodType $method): string => $method->label(), $definition->paymentMethods))]);
        }

        if (count($definition->segments) === 1) {
            $lines[] = $definition->segments[0] === CouponSegment::NewCustomers ? __('admin.coupons.preview.new_only') : __('admin.coupons.preview.returning_only');
        }

        if ($definition->schedule !== []) {
            $lines[] = __('admin.coupons.preview.schedule', ['windows' => implode('; ', array_map(
                static fn (array $window): string => __('enums.weekday.'.$window['day']).' '.$window['from'].'-'.$window['to'],
                $definition->schedule,
            ))]);
        }

        if ($coupon->per_customer_limit !== null) {
            $lines[] = __('admin.coupons.preview.per_customer', ['count' => $coupon->per_customer_limit]);
        }

        if ($coupon->global_limit !== null) {
            $lines[] = __('admin.coupons.preview.global', ['count' => $coupon->global_limit]);
        }

        if ($coupon->budget_minor !== null) {
            $lines[] = __('admin.coupons.preview.budget', ['amount' => $this->money($coupon->budget_minor, $coupon->currency)]);
        }

        if ($coupon->ends_at !== null) {
            $lines[] = __('admin.coupons.preview.until', ['date' => $this->date($coupon->ends_at, $coupon->timezone)]);
        }

        return $lines;
    }

    public function money(int $minor, string $currency): string
    {
        return $this->formatter->format(Money::of($minor, $this->currencies->get($currency)));
    }

    public function percent(int $basisPoints): string
    {
        return rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.').'%';
    }

    private function date(CarbonInterface $at, string $timezone): string
    {
        return $at->copy()->setTimezone($timezone)->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param list<int> $ids
     */
    private function names(string $model, array $ids): string
    {
        return $model::query()->whereKey($ids)->get()->map(static fn ($row): string => (string) $row->getAttribute('name'))->implode(', ');
    }
}
