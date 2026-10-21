<?php

namespace App\DTOs\Booking;

use App\Models\Booking;
use App\Models\BookingOffer;

/**
 * The booking after the customer chose an offer. `replayed` is true when this very offer had already been chosen
 * (a repeated tap or a retried request): nothing was written the second time.
 */
final readonly class OfferSelectionResult
{
    public function __construct(public Booking $booking, public BookingOffer $offer, public bool $replayed) {}
}
