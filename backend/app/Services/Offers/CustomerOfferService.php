<?php

namespace App\Services\Offers;

use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * The customer's read side of the negotiation: the offers they can choose now and, after selection, the chosen one.
 * Scoped by owner, so another customer's booking is a 404 (no existence oracle).
 */
final class CustomerOfferService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
    ) {}

    /**
     * Active, unexpired offers, cheapest first. An offer whose time ran out never appears, even before the sweep
     * closes it.
     *
     * @return Collection<int, BookingOffer>
     */
    public function openOffers(User $customer, string $bookingUuid): Collection
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $booking->status->isSearching() ? $this->offers->openForCustomer($booking->id, CarbonImmutable::now()) : new Collection;
    }

    /**
     * The accepted offer of an assigned booking, with the card relations.
     */
    public function selectedOffer(Booking $booking): ?BookingOffer
    {
        if ($booking->selected_offer_id === null) {
            return null;
        }

        $offer = $this->offers->find($booking->selected_offer_id);

        return $offer instanceof BookingOffer ? $this->offers->loadCard($offer) : null;
    }
}
