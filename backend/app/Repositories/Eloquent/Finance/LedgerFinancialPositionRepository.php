<?php

namespace App\Repositories\Eloquent\Finance;

use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Finance\PaymentPositionRepositoryInterface;

/**
 * The real financial position, read from the ledger (replaces the neutral implementation of ADR-92).
 *
 * - Open items = wallets with a non-zero balance (money owed either way) + open payments (payments seam, P3.2).
 * - Cash debt = how far the provider's wallets are below zero: commission owed on cash jobs (Plan §7.4), summed over the
 *   provider's wallets (one per currency; a market uses one currency today).
 * Payout requests (P3.4) will add to the provider's open items through their own seam.
 */
final class LedgerFinancialPositionRepository implements FinancialPositionRepositoryInterface
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly PaymentPositionRepositoryInterface $payments,
    ) {}

    public function openItemsForCustomer(User $customer): int
    {
        return $this->nonZero(LedgerAccountType::CustomerWallet, $customer->id) + $this->payments->openForCustomer($customer);
    }

    public function openItemsForProvider(ProviderProfile $profile): int
    {
        return $this->nonZero(LedgerAccountType::ProviderWallet, $profile->id) + $this->payments->openForProvider($profile);
    }

    public function cashDebtMinor(ProviderProfile $profile): int
    {
        return $this->accounts->forOwner(LedgerAccountType::ProviderWallet, $profile->id)
            ->sum(static fn (LedgerAccount $account): int => max(0, -$account->balance_minor));
    }

    private function nonZero(LedgerAccountType $type, int $ownerId): int
    {
        return $this->accounts->forOwner($type, $ownerId)->filter(static fn (LedgerAccount $account): bool => $account->balance_minor !== 0)->count();
    }
}
