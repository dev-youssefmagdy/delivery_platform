<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Models\User;
use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;

/**
 * Who may subscribe to which private channel (SRS §21, Plan §7.5). Callbacks in `routes/channels.php` delegate here;
 * a guessed channel name never helps because every decision is made on the server from the signed-in account:
 *
 * - `customer.{userUuid}`: that customer only (customer token).
 * - `provider.{providerUuid}`: that provider only (provider token).
 * - `booking.{bookingUuid}`: the booking's customer, or a provider that was dispatched the request or was selected.
 * - `admin.operations`: admins holding `bookings.view`.
 *
 * Accounts that are suspended or in deletion are refused (the token route also requires an active account).
 */
final class RealtimeChannelPolicy
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly BookingParticipationRepositoryInterface $bookings,
    ) {}

    public function joinCustomer(User $user, string $userUuid): bool
    {
        return $this->isActiveWithAbility($user, 'customer') && hash_equals($user->uuid, $userUuid);
    }

    public function joinProvider(User $user, string $providerUuid): bool
    {
        if (! $this->isActiveWithAbility($user, 'provider')) {
            return false;
        }

        $profile = $this->providers->findByUserId($user->id);

        return $profile !== null && hash_equals($profile->uuid, $providerUuid);
    }

    public function joinBooking(User $user, string $bookingUuid): bool
    {
        if ($this->isActiveWithAbility($user, 'customer')) {
            return $this->bookings->isCustomerOf($bookingUuid, $user->id);
        }

        if ($this->isActiveWithAbility($user, 'provider')) {
            $profile = $this->providers->findByUserId($user->id);

            return $profile !== null && $this->bookings->isProviderOf($bookingUuid, $profile->id);
        }

        return false;
    }

    public function joinAdminOperations(Admin $admin): bool
    {
        return $admin->can(AdminPermission::BookingsView->value);
    }

    private function isActiveWithAbility(User $user, string $ability): bool
    {
        return $user->status->blockCode() === null && ! $user->trashed() && $user->tokenCan($ability);
    }
}
