<?php

namespace App\Services\Finance;

use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Finance\PriceBreakdownInput;
use App\Exceptions\BusinessRuleViolation;

/**
 * The single producer of a booking's amounts (Plan I12, SRS §9.2). Pure: same input, same output, no clock, no
 * database. Order of computation:
 *
 * 1. provider gross = agreed fare + accepted add-ons (the commission base, ADR-18);
 * 2. the discount is capped at gross + customer fee and split between its funders with Money::allocate, so the
 *    parts always add up to the discount (no lost minor unit);
 * 3. tax = (gross + customer fee) x tax rate, rounded once; it is added on top and owed to the tax authority;
 * 4. commission on the gross (and on the tip when the rule version says so);
 * 5. customer payable = gross + fee + tax - discount + tip.
 */
final class PriceBreakdownCalculator
{
    public function __construct(private readonly CommissionCalculator $commissions) {}

    public function calculate(PriceBreakdownInput $input): PriceBreakdown
    {
        $currency = $input->agreedFare->currency;

        foreach ([$input->agreedFare, $input->addons, $input->discount, $input->customerFee, $input->tip] as $amount) {
            if ($amount->isNegative()) {
                throw BusinessRuleViolation::make('price_breakdown_negative_amount');
            }
        }

        if ($input->taxRateBp < 0 || $input->taxRateBp > 10_000) {
            throw BusinessRuleViolation::make('price_breakdown_tax_rate_invalid');
        }

        $gross = $input->agreedFare->add($input->addons);
        $discount = $input->discount->min($gross->add($input->customerFee));
        [$byPlatform, $byProvider, $byPartner] = $discount->allocate($input->funding->ratios());

        $tax = $gross->add($input->customerFee)->multiplyBasisPoints($input->taxRateBp);
        $commission = $this->commissions->commission($input->commission, $gross);
        $tipCommission = $this->commissions->tipCommission($input->commission, $input->tip);

        $payable = $gross->add($input->customerFee, $tax, $input->tip)->subtract($discount);
        $providerNet = $gross->subtract($commission, $byProvider);
        $providerEarning = $providerNet->add($input->tip)->subtract($tipCommission);
        $revenue = $commission->add($tipCommission, $input->customerFee);

        return new PriceBreakdown(
            currency: $currency,
            agreedFare: $input->agreedFare,
            addons: $input->addons,
            providerGross: $gross,
            discount: $discount,
            discountPlatform: $byPlatform,
            discountProvider: $byProvider,
            discountPartner: $byPartner,
            customerFee: $input->customerFee,
            taxRateBp: $input->taxRateBp,
            tax: $tax,
            tip: $input->tip,
            customerPayable: $payable,
            commission: $commission,
            tipCommission: $tipCommission,
            providerNet: $providerNet,
            providerEarning: $providerEarning,
            platformRevenue: $revenue,
            terms: $input->commission,
        );
    }
}
