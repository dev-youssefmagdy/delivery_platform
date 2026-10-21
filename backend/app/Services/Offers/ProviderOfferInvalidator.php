<?php

namespace App\Services\Offers;

use App\Enums\Booking\OfferInvalidationReason;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Illuminate\Database\DatabaseManager;

/**
 * A provider that went offline, lost its eligibility or was suspended can no longer be chosen: every active offer it
 * has is closed as `invalidated_provider_unavailable` (SRS §10.2 step 7) and a booking that was left without offers
 * returns to searching. Called by AvailabilityService whenever a provider leaves the index, which every cause
 * (own switch, stale position, suspension, expired document) goes through. Locks the booking first, like every
 * offer writer, so it cannot deadlock with a selection.
 */
final class ProviderOfferInvalidator
{
    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferCloser $closer,
        private readonly OfferSettlement $settlement,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return int offers closed
     */
    public function invalidateFor(ProviderProfile $provider): int
    {
        $closed = 0;

        foreach ($this->offers->activeBookingIdsForProvider($provider->id) as $bookingId) {
            $closed += $this->db->transaction(function () use ($provider, $bookingId): int {
                $booking = $this->bookings->lockById($bookingId);

                if (! $booking instanceof Booking) {
                    return 0;
                }

                $mine = $this->offers->lockActiveForBooking($bookingId)
                    ->filter(static fn (BookingOffer $offer): bool => $offer->provider_profile_id === $provider->id);

                $count = $this->closer->close($booking, $mine, OfferInvalidationReason::ProviderUnavailable->offerStatus(), OfferInvalidationReason::ProviderUnavailable->value);

                if ($booking->status->isSearching()) {
                    $this->settlement->afterOfferLoss($booking);
                }

                return $count;
            });
        }

        return $closed;
    }
}
