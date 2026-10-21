<?php

namespace App\Services\Offers;

use App\Enums\Booking\OfferStatus;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;

/**
 * Ends offers whose time ran out, without any client (SRS §10.2 step 6, Plan §2.3). Two triggers share this code: the
 * delayed `ExpireOffer` job at the exact second and the `offers:expire` sweep every 5 seconds. Both lock the booking,
 * then the offer, and re-check status and time under the lock, so an offer that was revised, accepted or closed
 * meanwhile is left alone and a second run changes nothing. When the last active offer ends and the search window is
 * still open, the booking returns to `searching_offers`.
 */
final class OfferExpiryService
{
    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferCloser $closer,
        private readonly OfferSettlement $settlement,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return bool whether this call expired the offer
     */
    public function expire(int $offerId, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();
        $found = $this->offers->find($offerId);

        if (! $found instanceof BookingOffer || ! $found->status->isActive()) {
            return false;
        }

        return $this->db->transaction(function () use ($found, $now): bool {
            $booking = $this->bookings->lockById($found->booking_id);
            $offer = $this->offers->lockOffer($found->id);

            if (! $booking instanceof Booking || ! $offer instanceof BookingOffer) {
                return false;
            }

            if (! $offer->status->isActive() || $offer->expires_at->greaterThan($now)) {
                return false;
            }

            $this->closer->close($booking, collect([$offer]), OfferStatus::Expired, 'offer_expired', $now);

            if ($booking->status->isSearching()) {
                $this->settlement->afterOfferLoss($booking, $now);
            }

            return true;
        });
    }

    /**
     * @return int offers expired
     */
    public function sweep(?int $limit = null): int
    {
        $now = CarbonImmutable::now();
        $count = 0;

        foreach ($this->offers->expiredIds($now, $limit ?? (int) config('booking.offers.sweep_batch', 200)) as $id) {
            if ($this->expire($id, $now)) {
                $count++;
            }
        }

        return $count;
    }
}
