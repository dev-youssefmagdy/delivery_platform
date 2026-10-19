<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Authorization of the settings screen (settings.view / settings.manage).
 */
class PlatformSettingPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::SettingsView->value);
    }

    public function update(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::SettingsManage->value);
    }
}
