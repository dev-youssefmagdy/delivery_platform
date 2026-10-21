<?php

namespace App\Services\Offers;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\OfferInvalidationReason;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * The booking module's view of offers (the interface P2.3 left as a seam, now backed by the real tables). The summary
 * counts offers the customer can choose right now; `invalidateActive` is called by the request change, search expiry
 * and cancellation flows inside the booking's lock and ends every active offer with the status the reason maps to,
 * telling the customer and each provider.
 */
final class BookingOfferBridge implements BookingOfferRepositoryInterface
{
    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly OfferCloser $closer,
    ) {}

    public function summary(Booking $booking): OffersSummary
    {
        $summary = $this->offers->openSummary($booking->id, CarbonImmutable::now());

        return new OffersSummary($summary['count'], null, $summary['lowest_minor']);
    }

    public function invalidateActive(Booking $booking, OfferInvalidationReason $reason): int
    {
        return $this->closer->close($booking, $this->offers->lockActiveForBooking($booking->id), $reason->offerStatus(), $reason->value);
    }
}
