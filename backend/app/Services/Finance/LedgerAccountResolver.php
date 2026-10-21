<?php

namespace App\Services\Finance;

use App\DTOs\Finance\SettlementAccounts;
use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use InvalidArgumentException;

/**
 * Finds or lazily opens the ledger account a posting needs: wallets per owner and currency, and the platform's own
 * accounts per currency. Opening an account is the only write besides postings, and it is race-safe.
 */
final class LedgerAccountResolver
{
    public function __construct(private readonly LedgerAccountRepositoryInterface $accounts) {}

    public function platform(LedgerAccountType $type, string $currency): LedgerAccount
    {
        if ($type->isOwned()) {
            throw new InvalidArgumentException("[{$type->value}] is an owned account.");
        }

        return $this->accounts->firstOrCreateFor($type, null, $currency);
    }

    public function customerWallet(User $customer, string $currency): LedgerAccount
    {
        return $this->accounts->firstOrCreateFor(LedgerAccountType::CustomerWallet, $customer->id, $currency);
    }

    public function providerWallet(ProviderProfile $provider, string $currency): LedgerAccount
    {
        return $this->accounts->firstOrCreateFor(LedgerAccountType::ProviderWallet, $provider->id, $currency);
    }

    public function settlementAccounts(ProviderProfile $provider, string $currency): SettlementAccounts
    {
        return new SettlementAccounts(
            providerWallet: $this->providerWallet($provider, $currency)->id,
            platformRevenue: $this->platform(LedgerAccountType::PlatformRevenue, $currency)->id,
            promoExpense: $this->platform(LedgerAccountType::PromoExpense, $currency)->id,
            partnerReceivable: $this->platform(LedgerAccountType::PartnerReceivable, $currency)->id,
            taxesPayable: $this->platform(LedgerAccountType::TaxesPayable, $currency)->id,
            gatewayClearing: $this->platform(LedgerAccountType::GatewayClearing, $currency)->id,
        );
    }
}
