<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Identity\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Admin authorization for customer accounts (the mobile `User` model). There is deliberately no create, update or
 * delete ability: accounts are created by the OTP flow and can only be anonymised through a processed deletion
 * request, never deleted from the panel.
 */
class UserPolicy
{
    /**
     * Statuses an account can be suspended from.
     */
    private const SUSPENDABLE = [UserStatus::Active, UserStatus::Restricted, UserStatus::VerificationRequired];

    /**
     * Statuses an account can be reactivated from.
     */
    private const REACTIVATABLE = [UserStatus::Suspended, UserStatus::Restricted, UserStatus::VerificationRequired];

    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CustomersView->value);
    }

    public function view(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersView->value);
    }

    /**
     * Reveal contact details and read the saved places (home/work addresses).
     */
    public function viewPii(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersViewPii->value) && ! $customer->isAnonymised();
    }

    public function suspend(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersSuspend->value) && in_array($customer->status, self::SUSPENDABLE, true);
    }

    public function reactivate(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersSuspend->value) && in_array($customer->status, self::REACTIVATABLE, true);
    }

    public function restrict(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersSuspend->value) && $customer->status === UserStatus::Active;
    }

    public function sendMessage(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersUpdate->value) && ! $customer->isAnonymised() && $customer->status !== UserStatus::Deleted;
    }

    public function processDeletion(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersProcessDeletion->value) && $customer->status === UserStatus::DeletionPending;
    }

    /**
     * Queue a CSV export of the customers listing.
     */
    public function export(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CustomersExport->value);
    }

    /**
     * Queue the JSON personal-data export of one customer.
     */
    public function exportData(Authorizable $admin, User $customer): bool
    {
        return $admin->can(AdminPermission::CustomersExport->value) && ! $customer->isAnonymised();
    }
}
