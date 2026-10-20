<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Admin authorization for provider applications and profiles (permissions of Plan Appendix A: drivers.*), plus the
 * ownership check the driver API uses (`manageOwn`). Buttons are hidden by these abilities and the routes and services
 * enforce them again: a read-only role gets 403 on every decision.
 */
class ProviderProfilePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::DriversView->value);
    }

    public function view(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversView->value);
    }

    /**
     * Reveal contact details and the birth date.
     */
    public function viewPii(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversViewPii->value) && ! $provider->user->isAnonymised();
    }

    /**
     * Approve, request more information, claim for review.
     */
    public function verify(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversVerify->value) && $provider->status === ProviderStatus::UnderReview;
    }

    public function claim(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversVerify->value) && $provider->status->isAwaitingReview();
    }

    public function reject(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversReject->value) && $provider->status->isAwaitingReview();
    }

    public function suspend(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversSuspend->value)
            && in_array($provider->status, [ProviderStatus::Approved, ProviderStatus::TemporarilySuspended], true);
    }

    public function reactivate(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversSuspend->value) && $provider->status === ProviderStatus::TemporarilySuspended;
    }

    /**
     * Approve or revoke one requested service of an approved provider.
     */
    public function decideService(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::DriversVerify->value)
            && in_array($provider->status, [ProviderStatus::Approved, ProviderStatus::TemporarilySuspended], true);
    }

    /**
     * Approve or reject the deletion request of a provider account (the legal action of `customers.process_deletion`).
     */
    public function processDeletion(Authorizable $admin, ProviderProfile $provider): bool
    {
        return $admin->can(AdminPermission::CustomersProcessDeletion->value)
            && $admin->can(AdminPermission::DriversView->value)
            && $provider->status === ProviderStatus::DeletionPending
            && ! $provider->user->isAnonymised();
    }

    /**
     * The driver API: a provider only ever touches its own profile.
     */
    public function manageOwn(Authorizable $actor, ProviderProfile $provider): bool
    {
        return $actor instanceof User && $provider->user_id === $actor->id;
    }
}
