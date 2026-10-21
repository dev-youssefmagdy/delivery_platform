<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<BookingStatusEvent>
 */
final class BookingStatusEventRepository extends BaseRepository implements BookingStatusEventRepositoryInterface
{
    public function __construct(BookingStatusEvent $model)
    {
        parent::__construct($model);
    }

    public function append(array $attributes): BookingStatusEvent
    {
        return $this->query()->create($attributes);
    }

    public function forBooking(Booking $booking): Collection
    {
        return $this->query()->where('booking_id', $booking->id)->orderBy('sequence')->get();
    }
}
