<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use App\Models\BookingStop;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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

        foreach ($stops as $index => $stop) {
            $this->query()->create([
                'booking_id' => $booking->id,
                'position' => $index + 1,
                'point' => $stop['point'],
                'address' => $stop['address'],
                'contact_encrypted' => ($stop['contact'] ?? null) === null ? null : Crypt::encryptString((string) json_encode($stop['contact'], JSON_UNESCAPED_UNICODE)),
            ]);
        }

        $booking->unsetRelation('stops');
    }

    public function scrubContactsForCustomer(int $customerId): int
    {
        return DB::table('booking_stops')
            ->whereIn('booking_id', DB::table('bookings')->where('customer_id', $customerId)->select('id'))
            ->whereNotNull('contact_encrypted')
            ->update(['contact_encrypted' => null, 'updated_at' => now()]);
    }
}
