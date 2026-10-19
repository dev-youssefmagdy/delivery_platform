<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for admin users (permissions: admins.view / admins.manage). Row-state rules
 * (self, already suspended …) keep dangerous buttons away; the service re-checks them under lock.
 */
class AdminPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::AdminsView->value);
    }

    public function view(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsView->value);
    }

    public function create(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value);
    }

    public function update(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value);
    }

    public function suspend(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value) && ! $this->isSelf($actor, $target) && $target->isActive();
    }

    public function reactivate(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value) && ! $target->isActive();
    }

    public function resetMfa(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value) && ! $this->isSelf($actor, $target) && $target->hasMfaEnabled();
    }

    public function delete(Authorizable $actor, Admin $target): bool
    {
        return $actor->can(AdminPermission::AdminsManage->value) && ! $this->isSelf($actor, $target);
    }

    private function isSelf(Authorizable $actor, Admin $target): bool
    {
        return $actor instanceof Admin && $actor->is($target);
    }
}
