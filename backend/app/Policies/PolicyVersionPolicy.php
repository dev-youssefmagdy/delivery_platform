<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\PolicyVersion;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for policy versions: policies.manage edits drafts, policies.publish publishes
 * them (Plan Appendix B grants publish separately). Published and archived versions are immutable.
 */
class PolicyVersionPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PoliciesManage->value);
    }

    public function view(Authorizable $admin, PolicyVersion $policyVersion): bool
    {
        return $admin->can(AdminPermission::PoliciesManage->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PoliciesManage->value);
    }

    public function update(Authorizable $admin, PolicyVersion $policyVersion): bool
    {
        return $admin->can(AdminPermission::PoliciesManage->value) && $policyVersion->isDraft();
    }

    public function delete(Authorizable $admin, PolicyVersion $policyVersion): bool
    {
        return $admin->can(AdminPermission::PoliciesManage->value) && $policyVersion->isDraft();
    }

    public function publish(Authorizable $admin, PolicyVersion $policyVersion): bool
    {
        return $admin->can(AdminPermission::PoliciesPublish->value) && $policyVersion->isDraft();
    }
}
