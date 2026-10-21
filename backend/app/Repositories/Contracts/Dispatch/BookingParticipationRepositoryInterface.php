<?php

namespace App\Repositories\Contracts\Dispatch;

/**
 * Who may listen to a booking's private channel. Deny-by-default: the Eloquent implementation answers false until the
 * booking does not exist; the owner is `bookings.customer_id`, providers are dispatched recipients or the selected provider.
 */
interface BookingParticipationRepositoryInterface
{
    public function isCustomerOf(string $bookingUuid, int $userId): bool;

    /**
     * The provider was dispatched the request or is its selected provider.
     */
    public function isProviderOf(string $bookingUuid, int $providerProfileId): bool;
}
