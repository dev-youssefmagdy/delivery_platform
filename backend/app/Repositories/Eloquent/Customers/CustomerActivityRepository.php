<?php

namespace App\Repositories\Eloquent\Customers;

use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;

/**
 * What a customer still has running: bookings in flight come from `bookings`; unsettled money from the financial
 * position: ledger wallets with a balance plus open payments (that part stays neutral until P3.2, ADR-115).
 */
final class CustomerActivityRepository implements CustomerActivityRepositoryInterface
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly FinancialPositionRepositoryInterface $finances,
    ) {}

    public function activeBookingCount(User $user): int
    {
        return $this->bookings->inFlightCountForCustomer($user->id);
    }

    public function openFinancialItemCount(User $user): int
    {
        return $this->finances->openItemsForCustomer($user);
    }
}
