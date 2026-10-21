<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\OfferInvalidationReason;
use App\Models\Booking;

/**
 * What the booking module needs from offers: a summary for the customer view and bulk invalidation when the request
 * changes, expires or is cancelled. The offer module (P2.4) owns the tables and replaces the bound implementation.
 */
interface BookingOfferRepositoryInterface
{
    public function summary(Booking $booking): OffersSummary;

    /**
     * Closes every active offer of the booking and returns how many there were.
     */
    public function invalidateActive(Booking $booking, OfferInvalidationReason $reason): int;
}
