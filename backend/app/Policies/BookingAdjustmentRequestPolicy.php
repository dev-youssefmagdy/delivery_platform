<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Booking\BookingAdjustmentStatus;
use App\Models\Admin;
use App\Models\BookingAdjustmentRequest;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Admin access to booking fare adjustments: read with `bookings.view`, void with `bookings.manage` (a pending or approved
 * request only). Operations never approve on behalf of a party; they can only void.
 */
class BookingAdjustmentRequestPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::BookingsView->value);
    }

    public function view(Authorizable $actor, BookingAdjustmentRequest $request): bool
    {
        return $this->viewAny($actor);
    }

    public function void(Authorizable $actor, BookingAdjustmentRequest $request): bool
    {
        return $actor instanceof Admin
            && $actor->can(AdminPermission::BookingsManage->value)
            && in_array($request->status, [BookingAdjustmentStatus::Pending, BookingAdjustmentStatus::Approved], true);
    }
}
