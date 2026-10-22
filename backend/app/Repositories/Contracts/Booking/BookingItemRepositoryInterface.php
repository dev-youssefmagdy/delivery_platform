<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Support\Collection;

interface BookingItemRepositoryInterface
{
    /**
     * @return Collection<int, BookingItem>
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * Replaces the booking's load with `$items` (attribute arrays of BookingItem without the booking id).
     *
     * @param list<array<string, mixed>> $items
     */
    public function replace(Booking $booking, array $items): void;

    /**
     * Removes the free text of every item of the customer's bookings (account deletion). Returns the rows touched.
     */
    public function scrubForCustomer(int $customerId): int;
}
