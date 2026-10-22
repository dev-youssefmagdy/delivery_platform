<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingReceipt;

interface BookingReceiptRepositoryInterface
{
    public function findForBooking(int $bookingId): ?BookingReceipt;

    /**
     * Inserts the receipt of a booking; a second insert for the same booking returns the first (the booking id is unique).
     *
     * @param array<string, mixed> $attributes
     */
    public function firstOrCreate(int $bookingId, array $attributes): BookingReceipt;
}
