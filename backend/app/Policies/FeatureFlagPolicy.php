<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Feature flags (system.feature_flags).
 */
class FeatureFlagPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::SystemFeatureFlags->value);
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
