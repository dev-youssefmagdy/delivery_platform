<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Failed queue jobs (system.queues): list and manual retry.
 */
class FailedJobPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::SystemQueues->value);
    }

    public function retry(Authorizable $admin): bool
    {
        return $this->viewAny($admin);
    }
}
