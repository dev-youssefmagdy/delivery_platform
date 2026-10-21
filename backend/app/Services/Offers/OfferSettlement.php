<?php

namespace App\Services\Offers;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Services\Booking\BookingTransitionService;
use Carbon\CarbonImmutable;

/**
 * What the booking does when an offer ended: if it was the last active one and the search window is still open, the
 * booking goes back from `offers_received` to `searching_offers` (SRS §10.2). The status change goes through
 * BookingTransitionService like every other; dispatch keeps running because the window did not change.
 */
final class OfferSettlement
{
    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly BookingTransitionService $transitions,
    ) {}

    /**
     * Call inside the booking's lock.
     */
    public function afterOfferLoss(Booking $locked, ?CarbonImmutable $now = null): Booking
    {
        $now ??= CarbonImmutable::now();

        if ($locked->status !== BookingStatus::OffersReceived) {
            return $locked;
        }

        if ($locked->search_expires_at === null || $locked->search_expires_at->lessThanOrEqualTo($now)) {
            return $locked;
        }

        if ($this->offers->lockActiveForBooking($locked->id)->isNotEmpty()) {
            return $locked;
        }

        try {
            return $this->transitions->transition($locked, BookingStatus::SearchingOffers, TransitionContext::system('offers_exhausted'));
        } catch (ConflictException) {
            return $locked;
        }
    }
}
