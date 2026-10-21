<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Object-level access to bookings for mobile users: the customer who owns it. (Providers see bookings through their
 * own, stage-limited resources from P2.4; admins through the admin policies.)
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->customer_id === $user->id;
    }

    public function updateRequest(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking) && $booking->status->isSearching();
    }
}
