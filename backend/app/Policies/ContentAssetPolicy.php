<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;

/**
 * Categories, app links and the media library: plain content.manage (no publishing workflow; every change is audited).
 */
class ContentAssetPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ContentManage->value);
    }

    public function view(Authorizable $admin, Model $asset): bool
    {
        return $this->viewAny($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->viewAny($admin);
    }

    public function update(Authorizable $admin, Model $asset): bool
    {
        return $this->viewAny($admin);
    }

    public function delete(Authorizable $admin, Model $asset): bool
    {
        return $this->viewAny($admin);
    }
}
