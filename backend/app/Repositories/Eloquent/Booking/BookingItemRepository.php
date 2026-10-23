<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookingItemRepository implements BookingItemRepositoryInterface
{
    public function forBooking(int $bookingId): Collection
    {
        return BookingItem::query()->where('booking_id', $bookingId)->orderBy('position')->orderBy('id')->get();
    }

    public function replace(Booking $booking, array $items): void
    {
        BookingItem::query()->where('booking_id', $booking->id)->delete();

        foreach ($items as $position => $item) {
            BookingItem::query()->create($item + ['booking_id' => $booking->id, 'position' => $position]);
        }
    }

    public function scrubForCustomer(int $customerId): int
    {
        return DB::table('booking_items')
            ->whereIn('booking_id', DB::table('bookings')->where('customer_id', $customerId)->select('id'))
            ->where(static fn ($query) => $query->whereNotNull('label')->orWhereNotNull('note'))
            ->update(['label' => null, 'note' => null, 'updated_at' => now()]);
    }
}
