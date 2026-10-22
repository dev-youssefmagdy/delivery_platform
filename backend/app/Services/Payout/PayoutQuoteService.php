<?php

namespace App\Services\Payout;

use App\DTOs\Payout\PayoutQuote;
use App\Enums\Settings\SettingKey;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;

/**
 * Fee and approval rule of a payout from the settings (never from the client): fee = fixed + percentage (basis points,
 * half-up on integer minor units), net = amount - fee, and the dual-approval flag at or above the threshold.
 */
final class PayoutQuoteService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function quote(int $amountMinor, string $currency): PayoutQuote
    {
        $money = Money::of($amountMinor, $this->currencies->get($currency));
        $percentage = $money->multiplyBasisPoints($this->settings->int(SettingKey::PayoutFeeBasisPoints))->amount;
        $fee = min($amountMinor, $percentage + $this->settings->int(SettingKey::PayoutFeeFixedMinor));

        return new PayoutQuote(
            amountMinor: $amountMinor,
            feeMinor: $fee,
            netMinor: $amountMinor - $fee,
            requiresDualApproval: $amountMinor >= $this->settings->int(SettingKey::PayoutDualApprovalThresholdMinor),
        );
    }

    public function minMinor(): int
    {
        return $this->settings->int(SettingKey::PayoutMinAmountMinor);
    }

    public function maxMinor(): int
    {
        return $this->settings->int(SettingKey::PayoutMaxAmountMinor);
    }
}
