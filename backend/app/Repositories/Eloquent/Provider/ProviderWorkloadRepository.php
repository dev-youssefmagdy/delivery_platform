<?php

namespace App\Repositories\Eloquent\Provider;

use App\DTOs\Location\ActiveBooking;
use App\Enums\Booking\BookingStatus;
use App\Enums\Location\TrackingState;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;

/**
 * The provider's current job comes from `bookings` (assigned statuses); cash debt comes from the financial position
 * seam, which is neutral until the ledger exists (P3.1, ADR-92).
 */
final class ProviderWorkloadRepository implements ProviderWorkloadRepositoryInterface
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly FinancialPositionRepositoryInterface $finances,
    ) {}

    public function activeBooking(ProviderProfile $profile): ?ActiveBooking
    {
        $booking = $this->bookings->currentForProvider($profile->id);

        if ($booking === null) {
            return null;
        }

        return new ActiveBooking($booking->id, $booking->uuid, match ($booking->status) {
            BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup => TrackingState::ToPickup,
            default => TrackingState::Active,
        });
    }

    public function cashDebtMinor(ProviderProfile $profile): int
    {
        return $this->finances->cashDebtMinor($profile);
    }
}
