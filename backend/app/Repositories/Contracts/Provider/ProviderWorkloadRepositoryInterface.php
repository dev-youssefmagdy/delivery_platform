<?php

namespace App\Repositories\Contracts\Provider;

use App\DTOs\Location\ActiveBooking;
use App\Models\ProviderProfile;

/**
 * What a provider is doing and owes right now, as far as going online and dispatch need to know.
 *
 * The active job comes from `bookings` (assigned statuses); cash debt comes from FinancialPositionRepositoryInterface,
 * which is neutral until the ledger exists (P3.1, ADR-92).
 */
interface ProviderWorkloadRepositoryInterface
{
    public function activeBooking(ProviderProfile $profile): ?ActiveBooking;

    /**
     * Commission the provider owes from cash jobs, in minor units (0 when the wallet is not negative).
     */
    public function cashDebtMinor(ProviderProfile $profile): int;
}
