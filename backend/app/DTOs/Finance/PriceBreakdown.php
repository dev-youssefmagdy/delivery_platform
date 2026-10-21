<?php

namespace App\DTOs\Finance;

use App\Support\Money\Currency;
use App\Support\Money\Money;

/**
 * Every amount of a finished trip, produced once by PriceBreakdownCalculator and stored on the booking (SRS §9.2:
 * keep customer payable, provider gross, commission, discount funding, tax and net apart; never infer one from
 * another). All Money in the booking's currency.
 *
 * - `providerGross` = agreed fare + accepted add-ons (the commission base, ADR-18).
 * - `providerNet` = providerGross - commission - provider-funded discount (the fare part, tips excluded).
 * - `providerEarning` = providerNet + tip - tip commission.
 * - `platformRevenue` = commission + tip commission + customer fee. Platform-funded discounts are a cost, not a deduction.
 */
final readonly class PriceBreakdown
{
    public function __construct(
        public Currency $currency,
        public Money $agreedFare,
        public Money $addons,
        public Money $providerGross,
        public Money $discount,
        public Money $discountPlatform,
        public Money $discountProvider,
        public Money $discountPartner,
        public Money $customerFee,
        public int $taxRateBp,
        public Money $tax,
        public Money $tip,
        public Money $customerPayable,
        public Money $commission,
        public Money $tipCommission,
        public Money $providerNet,
        public Money $providerEarning,
        public Money $platformRevenue,
        public CommissionTerms $terms,
    ) {}

    public function totalCommission(): Money
    {
        return $this->commission->add($this->tipCommission);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $minor = static fn (Money $money): int => $money->amount;

        return [
            'currency' => $this->currency->code,
            'agreed_fare_minor' => $minor($this->agreedFare),
            'addons_minor' => $minor($this->addons),
            'provider_gross_minor' => $minor($this->providerGross),
            'discount_minor' => $minor($this->discount),
            'discount_platform_minor' => $minor($this->discountPlatform),
            'discount_provider_minor' => $minor($this->discountProvider),
            'discount_partner_minor' => $minor($this->discountPartner),
            'customer_fee_minor' => $minor($this->customerFee),
            'tax_rate_bp' => $this->taxRateBp,
            'tax_minor' => $minor($this->tax),
            'tip_minor' => $minor($this->tip),
            'customer_payable_minor' => $minor($this->customerPayable),
            'commission_minor' => $minor($this->commission),
            'tip_commission_minor' => $minor($this->tipCommission),
            'provider_net_minor' => $minor($this->providerNet),
            'provider_earning_minor' => $minor($this->providerEarning),
            'platform_revenue_minor' => $minor($this->platformRevenue),
            'commission_terms' => $this->terms->toSnapshot(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, Currency $currency): self
    {
        $money = static fn (string $key): Money => Money::of((int) ($data[$key] ?? 0), $currency);

        return new self(
            currency: $currency,
            agreedFare: $money('agreed_fare_minor'),
            addons: $money('addons_minor'),
            providerGross: $money('provider_gross_minor'),
            discount: $money('discount_minor'),
            discountPlatform: $money('discount_platform_minor'),
            discountProvider: $money('discount_provider_minor'),
            discountPartner: $money('discount_partner_minor'),
            customerFee: $money('customer_fee_minor'),
            taxRateBp: (int) ($data['tax_rate_bp'] ?? 0),
            tax: $money('tax_minor'),
            tip: $money('tip_minor'),
            customerPayable: $money('customer_payable_minor'),
            commission: $money('commission_minor'),
            tipCommission: $money('tip_commission_minor'),
            providerNet: $money('provider_net_minor'),
            providerEarning: $money('provider_earning_minor'),
            platformRevenue: $money('platform_revenue_minor'),
            terms: CommissionTerms::fromSnapshot((array) $data['commission_terms']),
        );
    }
}
