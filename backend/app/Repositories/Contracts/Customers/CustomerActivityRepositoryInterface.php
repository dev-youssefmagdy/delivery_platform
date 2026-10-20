<?php

namespace App\Repositories\Contracts\Customers;

use App\Models\User;

/**
 * What a customer still has running with the platform. Deleting the account is refused while any of it is open.
 * The Eloquent implementation is a seam: bookings arrive in P2.3 and the ledger/payments in P3.1, which must
 * replace the placeholder counts.
 */
interface CustomerActivityRepositoryInterface
{
    public function activeBookingCount(User $user): int;

    /**
     * Unsettled money in either direction (unpaid fares, pending refunds, wallet balance owed).
     */
    public function openFinancialItemCount(User $user): int;
}
