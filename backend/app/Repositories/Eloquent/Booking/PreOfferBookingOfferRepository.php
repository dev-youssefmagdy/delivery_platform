<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\OfferInvalidationReason;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * No offers can exist before P2.4 (there is no `booking_offers` table), so "no active offers" is the true answer.
 * As soon as the table exists this implementation throws instead of silently under-reporting: P2.4 rebinds the
 * interface to the offer repository.
 */
final class PreOfferBookingOfferRepository implements BookingOfferRepositoryInterface
{
    public function summary(Booking $booking): OffersSummary
    {
        $this->assertNoOfferTable();

        return new OffersSummary;
    }

    public function invalidateActive(Booking $booking, OfferInvalidationReason $reason): int
    {
        $this->assertNoOfferTable();

        return 0;
    }

    private function assertNoOfferTable(): void
    {
        if (Schema::hasTable('booking_offers')) {
            throw new LogicException('BookingOfferRepositoryInterface must be bound to the offer repository now that booking_offers exists (P2.4).');
        }
    }
}
