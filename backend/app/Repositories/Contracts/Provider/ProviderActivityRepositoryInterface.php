<?php

namespace App\Repositories\Contracts\Provider;

use App\Models\ProviderProfile;

/**
 * What a provider still has running with the platform. Processing an account deletion is refused while any of it is
 * open. The Eloquent implementation is a seam: bookings arrive in P2.3 and wallets/payouts in P3.1/P3.4, which must
 * replace the placeholder counts.
 */
interface ProviderActivityRepositoryInterface
{
    public function activeJobCount(ProviderProfile $profile): int;

    /**
     * Unsettled money in either direction (wallet balance, commission due, pending payouts).
     */
    public function openFinancialItemCount(ProviderProfile $profile): int;
}
