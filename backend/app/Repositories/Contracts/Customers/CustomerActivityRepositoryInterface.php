<?php

namespace App\Repositories\Contracts\Customers;

use App\Models\User;

/**
 * What a customer still has running with the platform. Deleting the account is refused while any of it is open.
 * Bookings in flight are counted from `bookings`; unsettled money comes from FinancialPositionRepositoryInterface,
 * neutral until the ledger and payments exist (P3.1 / P3.2, ADR-92).
 */
interface CustomerActivityRepositoryInterface
{
    public function activeBookingCount(User $user): int;

    /**
     * Unsettled money in either direction (unpaid fares, pending refunds, wallet balance owed).
     */
    public function openFinancialItemCount(User $user): int;
}
