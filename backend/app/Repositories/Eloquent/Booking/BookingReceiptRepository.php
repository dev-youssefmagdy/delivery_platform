<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\BookingReceipt;
use App\Repositories\Contracts\Booking\BookingReceiptRepositoryInterface;

final class BookingReceiptRepository implements BookingReceiptRepositoryInterface
{
    public function findForBooking(int $bookingId): ?BookingReceipt
    {
        return BookingReceipt::query()->where('booking_id', $bookingId)->first();
    }

    public function firstOrCreate(int $bookingId, array $attributes): BookingReceipt
    {
        return $this->findForBooking($bookingId) ?? BookingReceipt::query()->create($attributes + ['booking_id' => $bookingId]);
    }
}
