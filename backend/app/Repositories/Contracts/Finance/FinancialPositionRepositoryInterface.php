<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\ProviderProfile;
use App\Models\User;

/**
 * What a customer or provider owes or is owed that is not settled yet. Account deletion and going online read it.
 *
 * Backed by the ledger since P3.1 (wallet balances and cash debt) plus the payments seam (open payments, P3.2) and,
 * in P3.4, payout requests. Callers did not change when the neutral implementation of ADR-92 was replaced (ADR-115).
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
