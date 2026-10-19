<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Read-only login activity (login_activity.view).
 */
class AdminLoginEventPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::LoginActivityView->value);
    }
}
