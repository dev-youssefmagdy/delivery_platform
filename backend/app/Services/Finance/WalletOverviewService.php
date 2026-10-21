<?php

namespace App\Services\Finance;

use App\DTOs\Finance\WalletOverview;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Services\Settings\SettingsService;

/**
 * What the wallet tabs of the customer and provider pages show: the wallets with their balances and, for a provider,
 * the cash debt against the limit that blocks going online (`CashDebtLimitExceeded`).
 */
final class WalletOverviewService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly FinancialPositionRepositoryInterface $position,
        private readonly SettingsService $settings,
    ) {}

    public function forCustomer(User $customer): WalletOverview
    {
        return new WalletOverview($this->accounts->forOwner(LedgerAccountType::CustomerWallet, $customer->id)->all(), null, null);
    }

    public function forProvider(ProviderProfile $provider): WalletOverview
    {
        return new WalletOverview(
            $this->accounts->forOwner(LedgerAccountType::ProviderWallet, $provider->id)->all(),
            $this->position->cashDebtMinor($provider),
            $this->settings->int(SettingKey::ProviderCashDebtLimitMinor),
        );
    }
}
