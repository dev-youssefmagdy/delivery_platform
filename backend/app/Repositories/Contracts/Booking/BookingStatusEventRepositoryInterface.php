<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Append-only history of a booking.
 *
 * @extends BaseRepositoryInterface<BookingStatusEvent>
 */
interface BookingStatusEventRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function append(array $attributes): BookingStatusEvent;

    /**
     * @return Collection<int, BookingStatusEvent>
     */
    public function forBooking(Booking $booking): Collection;
}
