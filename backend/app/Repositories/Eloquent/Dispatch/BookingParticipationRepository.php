<?php

namespace App\Repositories\Eloquent\Dispatch;

use App\Models\Booking;
use App\Models\DispatchRecipient;
use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;

/**
 * Channel access for booking streams, against the real schema: `bookings.customer_id` is the owner, a provider takes part
 * when it was dispatched the request (`booking_dispatch_recipients`) or is the booking's selected provider
 * (`bookings.provider_id`). Everything else is denied.
 */
final class BookingParticipationRepository implements BookingParticipationRepositoryInterface
{
    public function isCustomerOf(string $bookingUuid, int $userId): bool
    {
        return Booking::query()->where('uuid', $bookingUuid)->where('customer_id', $userId)->exists();
    }

    public function isProviderOf(string $bookingUuid, int $providerProfileId): bool
    {
        $booking = Booking::query()->select(['id', 'provider_id'])->where('uuid', $bookingUuid)->first();

        if ($booking === null) {
            return false;
        }

        return $booking->provider_id === $providerProfileId
            || DispatchRecipient::query()->where('booking_id', $booking->id)->where('provider_profile_id', $providerProfileId)->exists();
    }
}
