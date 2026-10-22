<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\BookingRecipientOtp;
use App\Repositories\Contracts\Booking\RecipientOtpRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class RecipientOtpRepository implements RecipientOtpRepositoryInterface
{
    public function find(int $bookingId): ?BookingRecipientOtp
    {
        return BookingRecipientOtp::query()->where('booking_id', $bookingId)->first();
    }

    public function lockForBooking(int $bookingId): ?BookingRecipientOtp
    {
        return BookingRecipientOtp::query()->where('booking_id', $bookingId)->lockForUpdate()->first();
    }

    public function upsert(int $bookingId, array $attributes): BookingRecipientOtp
    {
        return BookingRecipientOtp::query()->updateOrCreate(['booking_id' => $bookingId], $attributes);
    }

    public function update(BookingRecipientOtp $otp, array $attributes): BookingRecipientOtp
    {
        $otp->forceFill($attributes)->save();

        return $otp;
    }

    public function purgeForCustomer(int $customerId): int
    {
        return DB::table('booking_recipient_otps')
            ->whereIn('booking_id', DB::table('bookings')->where('customer_id', $customerId)->select('id'))
            ->delete();
    }
}
