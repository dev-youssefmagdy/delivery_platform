<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Provider\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Admin authorization for vehicles (vehicles.view / approve / reject) and the ownership check of the driver API.
 */
class VehiclePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::VehiclesView->value);
    }

    public function view(Authorizable $admin, Vehicle $vehicle): bool
    {
        return $admin->can(AdminPermission::VehiclesView->value);
    }

    public function approve(Authorizable $admin, Vehicle $vehicle): bool
    {
        return $admin->can(AdminPermission::VehiclesApprove->value) && $vehicle->status === VehicleStatus::UnderReview;
    }

    public function reject(Authorizable $admin, Vehicle $vehicle): bool
    {
        return $admin->can(AdminPermission::VehiclesReject->value) && $vehicle->status === VehicleStatus::UnderReview;
    }

    /**
     * The driver API: a provider only ever touches its own vehicles.
     */
    public function manageOwn(Authorizable $actor, Vehicle $vehicle): bool
    {
        return $actor instanceof User && $actor->providerProfile?->id === $vehicle->provider_profile_id;
    }
}
