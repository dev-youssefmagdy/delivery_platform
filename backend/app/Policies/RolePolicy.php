<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Role;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for roles (roles.view / roles.manage). The Super Admin role cannot be edited
 * (it always has every permission) and system roles cannot be deleted.
 */
class RolePolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::RolesView->value);
    }

    public function view(Authorizable $actor, Role $role): bool
    {
        return $actor->can(AdminPermission::RolesView->value);
    }

    public function create(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::RolesManage->value);
    }

    public function update(Authorizable $actor, Role $role): bool
    {
        return $actor->can(AdminPermission::RolesManage->value) && ! $role->isSuperAdmin();
    }

    public function delete(Authorizable $actor, Role $role): bool
    {
        return $actor->can(AdminPermission::RolesManage->value) && ! $role->is_system;
    }
}
