<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * The referral rewards listing (referrals.view). Rewards are written by the ledger flow only; nobody edits them.
 */
class ReferralRewardPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReferralsView->value);
    }
}
