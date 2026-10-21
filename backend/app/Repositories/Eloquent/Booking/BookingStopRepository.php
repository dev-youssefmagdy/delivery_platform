<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use App\Models\BookingStop;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

/**
 * @extends BaseRepository<BookingStop>
 */
final class BookingStopRepository extends BaseRepository implements BookingStopRepositoryInterface
{
    public function __construct(BookingStop $model)
    {
        parent::__construct($model);
    }

    public function replaceFor(Booking $booking, array $stops): void
    {
        $this->query()->where('booking_id', $booking->id)->delete();

        foreach (array_values($stops) as $index => $stop) {
            $this->query()->create([
                'booking_id' => $booking->id,
                'position' => $index + 1,
                'point' => $stop['point'],
                'address' => $stop['address'],
            ]);
        }

        $booking->unsetRelation('stops');
    }
}
