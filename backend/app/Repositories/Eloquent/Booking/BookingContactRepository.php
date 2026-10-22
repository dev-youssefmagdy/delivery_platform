<?php

namespace App\Repositories\Eloquent\Booking;

use App\Enums\Booking\ContactRole;
use App\Models\Booking;
use App\Models\BookingContact;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class BookingContactRepository implements BookingContactRepositoryInterface
{
    public function forBooking(int $bookingId): Collection
    {
        return BookingContact::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }

    public function find(int $bookingId, ContactRole $role): ?BookingContact
    {
        return BookingContact::query()->where('booking_id', $bookingId)->where('role', $role->value)->first();
    }

    public function replace(Booking $booking, array $contacts): void
    {
        BookingContact::query()->where('booking_id', $booking->id)->whereNotIn('role', array_keys($contacts))->delete();

        foreach ($contacts as $role => $contact) {
            BookingContact::query()->updateOrCreate(
                ['booking_id' => $booking->id, 'role' => $role],
                ['name_encrypted' => Crypt::encryptString($contact['name']), 'phone_encrypted' => Crypt::encryptString($contact['phone'])],
            );
        }
    }

    public function scrubForCustomer(int $customerId): int
    {
        return DB::table('booking_contacts')
            ->whereIn('booking_id', DB::table('bookings')->where('customer_id', $customerId)->select('id'))
            ->update(['name_encrypted' => null, 'phone_encrypted' => null, 'updated_at' => now()]);
    }
}
