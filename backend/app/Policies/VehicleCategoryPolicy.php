<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\VehicleCategory;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for VehicleCategory: a single permission (vehicle_categories.manage) covers viewing and managing (Plan Appendix A).
 */
class VehicleCategoryPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, VehicleCategory $model): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, VehicleCategory $model): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, VehicleCategory $model): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, VehicleCategory $model): bool
    {
        return $this->allowed($admin) && ! $model->active;
    }

    public function deactivate(Authorizable $admin, VehicleCategory $model): bool
    {
        return $this->allowed($admin) && $model->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::VehicleCategoriesManage->value);
    }
}
