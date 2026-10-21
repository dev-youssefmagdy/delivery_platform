<?php

namespace App\Repositories\Eloquent\Provider;

use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderActivityRepositoryInterface;

/**
 * What a provider still has running: assigned bookings come from `bookings`, unsettled money from the financial
 * position seam (neutral until the ledger exists, ADR-92).
 */
final class ProviderActivityRepository implements ProviderActivityRepositoryInterface
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly FinancialPositionRepositoryInterface $finances,
    ) {}

    public function activeJobCount(ProviderProfile $profile): int
    {
        return $this->bookings->assignedCountForProvider($profile->id);
    }

    public function openFinancialItemCount(ProviderProfile $profile): int
    {
        return $this->finances->openItemsForProvider($profile);
    }
}
