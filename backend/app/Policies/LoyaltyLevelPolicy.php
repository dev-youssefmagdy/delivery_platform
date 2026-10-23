<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\LoyaltyLevel;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Loyalty levels (loyalty.view / loyalty.manage).
 */
class LoyaltyLevelPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::LoyaltyView->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::LoyaltyManage->value);
    }

    public function update(Authorizable $admin, LoyaltyLevel $level): bool
    {
        return $admin->can(AdminPermission::LoyaltyManage->value);
    }

    public function toggle(Authorizable $admin, LoyaltyLevel $level): bool
    {
        return $admin->can(AdminPermission::LoyaltyManage->value);
    }
}
