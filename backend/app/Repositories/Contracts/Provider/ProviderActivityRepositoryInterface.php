<?php

namespace App\Repositories\Contracts\Provider;

use App\Models\ProviderProfile;

/**
 * What a provider still has running with the platform. Processing an account deletion is refused while any of it is
 * open. Assigned bookings are counted from `bookings`; unsettled money comes from FinancialPositionRepositoryInterface,
 * neutral until the ledger, wallets and payouts exist (P3.1 / P3.4, ADR-92).
 */
interface ProviderActivityRepositoryInterface
{
    public function activeJobCount(ProviderProfile $profile): int;

    /**
     * Unsettled money in either direction (wallet balance, commission due, pending payouts).
     */
    public function openFinancialItemCount(ProviderProfile $profile): int;
}
