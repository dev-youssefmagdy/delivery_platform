<?php

namespace App\Repositories\Contracts\Provider;

use App\DTOs\Location\ActiveBooking;
use App\Models\ProviderProfile;

/**
 * What a provider is doing and owes right now, as far as going online and dispatch need to know.
 *
 * Until P2.3 (bookings) and P3.1 (wallets) the Eloquent implementation honestly answers "nothing active, nothing owed"
 * and throws a LogicException as soon as the `bookings` or `wallets` table exists, so it cannot silently stay a stub.
 */
interface ProviderWorkloadRepositoryInterface
{
    public function activeBooking(ProviderProfile $profile): ?ActiveBooking;

    /**
     * Commission the provider owes from cash jobs, in minor units (0 when the wallet is not negative).
     */
    public function cashDebtMinor(ProviderProfile $profile): int;
}
