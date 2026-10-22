<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\SupportMacro;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Response templates for support agents (macros.manage).
 */
class SupportMacroPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, SupportMacro $macro): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, SupportMacro $macro): bool
    {
        return $this->allowed($admin);
    }

    public function toggle(Authorizable $admin, SupportMacro $macro): bool
    {
        return $this->allowed($admin);
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::MacrosManage->value);
    }
}
