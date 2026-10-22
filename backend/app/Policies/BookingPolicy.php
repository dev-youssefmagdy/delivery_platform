<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level access to bookings. Mobile users: the customer who owns it (providers see bookings through their own,
 * stage-limited resources). Admins (permissions of Plan Appendix A: bookings.*): viewing, exact addresses, and one
 * ability per operator override. The abilities only check permissions; whether the booking's current status allows the
 * action is decided under the row lock by `BookingOverrideService` (409 `override_not_available`).
 */
class BookingPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsView->value);
    }

    public function view(Authorizable $actor, Booking $booking): bool
    {
        if ($actor instanceof Admin) {
            return $actor->can(AdminPermission::BookingsView->value);
        }

        return $actor instanceof User && $booking->customer_id === $actor->id;
    }

    public function updateRequest(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking) && $booking->status->isSearching();
    }

    /**
     * Exact addresses and coordinates (phones stay masked for everybody).
     */
    public function viewPii(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsViewPii->value);
    }

    public function cancelByOperations(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsCancel->value);
    }

    /**
     * Extend the search, re-dispatch now, contact the customer of a booking without offers.
     */
    public function manage(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsManage->value);
    }

    public function forceTransition(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsAssignOverride->value);
    }

    public function liftPinLock(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsPinOverride->value);
    }

    public function liftRecipientOtpLock(Authorizable $actor, Booking $booking): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsPinOverride->value);
    }
}
