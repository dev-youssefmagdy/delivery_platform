<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\FarePolicy;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Fare policies: one permission (fare_policies.manage) covers viewing and managing.
 */
class FarePolicyPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, FarePolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, FarePolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, FarePolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, FarePolicy $policy): bool
    {
        return $this->allowed($admin) && ! $policy->active;
    }

    public function deactivate(Authorizable $admin, FarePolicy $policy): bool
    {
        return $this->allowed($admin) && $policy->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FarePoliciesManage->value);
    }
}
