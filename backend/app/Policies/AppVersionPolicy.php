<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * App versions (system.app_versions): one permission for viewing and changing minimum versions, force update and store links.
 */
class AppVersionPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::SystemAppVersions->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->viewAny($admin);
    }

    public function update(Authorizable $admin): bool
    {
        return $this->viewAny($admin);
    }
}
