<?php

namespace App\Repositories\Eloquent\Finance;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;

/**
 * Neutral implementation used until the money core exists (P3.1). With no wallets, payments, journal entries or
 * payouts nothing can be owed, so zero is the true answer, not a stub that hides data. It is bound in
 * RepositoryServiceProvider and replaced there by the ledger-backed repository in P3.1 (documented in ADR-92).
 */
final class PreLedgerFinancialPositionRepository implements FinancialPositionRepositoryInterface
{
    public function openItemsForCustomer(User $customer): int
    {
        return 0;
    }

    public function openItemsForProvider(ProviderProfile $profile): int
    {
        return 0;
    }

    public function cashDebtMinor(ProviderProfile $profile): int
    {
        return 0;
    }
}
