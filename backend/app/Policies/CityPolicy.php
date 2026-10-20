<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\City;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for City: the single `service_areas.manage` permission covers viewing and managing (Plan Appendix A).
 */
class CityPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, City $city): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, City $city): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, City $city): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, City $city): bool
    {
        return $this->allowed($admin) && ! $city->active;
    }

    public function deactivate(Authorizable $admin, City $city): bool
    {
        return $this->allowed($admin) && $city->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ServiceAreasManage->value);
    }
}
