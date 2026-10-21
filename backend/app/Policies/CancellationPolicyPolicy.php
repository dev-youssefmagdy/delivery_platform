<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\CancellationPolicy;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Cancellation policies: one permission (cancellation_policies.manage) covers viewing and managing.
 */
class CancellationPolicyPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, CancellationPolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, CancellationPolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, CancellationPolicy $policy): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, CancellationPolicy $policy): bool
    {
        return $this->allowed($admin) && ! $policy->active;
    }

    public function deactivate(Authorizable $admin, CancellationPolicy $policy): bool
    {
        return $this->allowed($admin) && $policy->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CancellationPoliciesManage->value);
    }
}
