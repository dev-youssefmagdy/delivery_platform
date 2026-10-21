<?php

namespace App\Repositories\Contracts\Dispatch;

/**
 * Who may listen to a booking's private channel. Deny-by-default: the Eloquent implementation answers false until the
 * bookings table exists (P2.3 completes it).
 */
interface BookingParticipationRepositoryInterface
{
    public function isCustomerOf(string $bookingUuid, int $userId): bool;

    /**
     * The provider was dispatched the request or is its selected provider.
     */
    public function isProviderOf(string $bookingUuid, int $providerProfileId): bool;
}
