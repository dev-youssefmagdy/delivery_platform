<?php

namespace App\Services\Payout;

use App\DTOs\Payout\ProviderBalance;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Settings\SettingKey;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Services\Settings\SettingsService;

/**
 * What a provider can withdraw (SRS §12.3: "available balance = balance - reservations"). A payout reservation is itself a
 * ledger entry (wallet -> payout clearing), so the wallet's cached balance is ALREADY net of every open payout; subtracting
 * the reservations again would count them twice. `reservedMinor` is reported separately for transparency. A wallet below zero
 * (cash commission owed) has nothing available.
 */
final class ProviderBalanceService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly PayoutQuoteService $quotes,
        private readonly SettingsService $settings,
    ) {}

    public function currency(): string
    {
        return (string) $this->settings->string(SettingKey::DefaultCurrency);
    }

    public function forProvider(ProviderProfile $provider, ?string $currency = null): ProviderBalance
    {
        $currency ??= $this->currency();
        $wallet = $this->accounts->forOwner(LedgerAccountType::ProviderWallet, $provider->id)->first(static fn (LedgerAccount $account): bool => $account->currency === $currency);
        $balance = $wallet instanceof LedgerAccount ? $wallet->balance_minor : 0;

        return new ProviderBalance(
            currency: $currency,
            walletMinor: $balance,
            reservedMinor: $this->payouts->reservedMinor($provider->id, $currency),
            availableMinor: max(0, $balance),
            minMinor: $this->quotes->minMinor(),
            maxMinor: $this->quotes->maxMinor(),
        );
    }
}
