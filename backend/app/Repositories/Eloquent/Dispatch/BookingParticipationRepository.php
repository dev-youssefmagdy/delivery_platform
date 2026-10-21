<?php

namespace App\Repositories\Eloquent\Dispatch;

use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Channel access for booking streams. Without a bookings table nobody participates in anything, which is the safe
 * answer. P2.3 only needs to add the selected-provider branch; dispatched recipients already work through the join.
 */
final class BookingParticipationRepository implements BookingParticipationRepositoryInterface
{
    public function isCustomerOf(string $bookingUuid, int $userId): bool
    {
        if (! Schema::hasTable('bookings')) {
            return false;
        }

        return DB::table('bookings')->where('uuid', $bookingUuid)->where('customer_user_id', $userId)->exists();
    }

    public function isProviderOf(string $bookingUuid, int $providerProfileId): bool
    {
        if (! Schema::hasTable('bookings')) {
            return false;
        }

        return DB::table('booking_dispatch_recipients')
            ->join('bookings', 'bookings.id', '=', 'booking_dispatch_recipients.booking_id')
            ->where('bookings.uuid', $bookingUuid)
            ->where('booking_dispatch_recipients.provider_profile_id', $providerProfileId)
            ->exists();
    }
}
