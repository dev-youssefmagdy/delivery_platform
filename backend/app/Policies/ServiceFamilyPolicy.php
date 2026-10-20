<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\ServiceFamily;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for ServiceFamily: a single permission (services.manage) covers viewing and managing (Plan Appendix A).
 */
class ServiceFamilyPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, ServiceFamily $model): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, ServiceFamily $model): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, ServiceFamily $model): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, ServiceFamily $model): bool
    {
        return $this->allowed($admin) && ! $model->active;
    }

    public function deactivate(Authorizable $admin, ServiceFamily $model): bool
    {
        return $this->allowed($admin) && $model->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ServicesManage->value);
    }
}
