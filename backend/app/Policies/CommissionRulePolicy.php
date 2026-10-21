<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\CommissionRule;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Commission rules: one permission (commission_rules.manage) covers viewing and managing. Rules are disabled, never
 * deleted, so there is no delete ability.
 */
class CommissionRulePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, CommissionRule $rule): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, CommissionRule $rule): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, CommissionRule $rule): bool
    {
        return $this->allowed($admin) && ! $rule->active;
    }

    public function deactivate(Authorizable $admin, CommissionRule $rule): bool
    {
        return $this->allowed($admin) && $rule->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CommissionRulesManage->value);
    }
}
