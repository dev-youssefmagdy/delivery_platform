<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\ProviderPayoutMethod;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Payout methods of providers: seen with `payouts.view` (masked), verified or un-verified with `payouts.review`.
 */
class ProviderPayoutMethodPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function view(Authorizable $admin, ProviderPayoutMethod $method): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function verify(Authorizable $admin, ProviderPayoutMethod $method): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value) && ! $method->isVerified() && ! $method->trashed();
    }

    public function unverify(Authorizable $admin, ProviderPayoutMethod $method): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value) && $method->isVerified() && ! $method->trashed();
    }
}
