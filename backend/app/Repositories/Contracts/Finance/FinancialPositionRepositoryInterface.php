<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\ProviderProfile;
use App\Models\User;

/**
 * What a customer or provider owes or is owed that is not settled yet. Account deletion and going online read it.
 *
 * Until the ledger exists (P3.1) the bound implementation answers "nothing" by construction: there is no wallet,
 * payment or payout table that could hold anything. P3.1 / P3.2 / P3.4 rebind this interface to ledger-backed
 * queries; callers do not change.
 */
interface FinancialPositionRepositoryInterface
{
    /**
     * Unsettled money in either direction for a customer (unpaid fares, pending refunds, wallet balance owed).
     */
    public function openItemsForCustomer(User $customer): int;

    /**
     * Unsettled money in either direction for a provider (wallet balance, commission due, pending payouts).
     */
    public function openItemsForProvider(ProviderProfile $profile): int;

    /**
     * Commission the provider owes from cash jobs, in minor units (0 when the wallet is not negative).
     */
    public function cashDebtMinor(ProviderProfile $profile): int;
}
