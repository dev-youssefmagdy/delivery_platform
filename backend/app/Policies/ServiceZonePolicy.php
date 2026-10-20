<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Geo\ZoneStatus;
use App\Models\ServiceZone;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for ServiceZone: `service_areas.manage` covers viewing and managing (Plan Appendix A).
 */
class ServiceZonePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, ServiceZone $zone): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, ServiceZone $zone): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, ServiceZone $zone): bool
    {
        return $this->allowed($admin) && $zone->status !== ZoneStatus::Active;
    }

    public function activate(Authorizable $admin, ServiceZone $zone): bool
    {
        return $this->allowed($admin) && $zone->status !== ZoneStatus::Active;
    }

    public function suspend(Authorizable $admin, ServiceZone $zone): bool
    {
        return $this->allowed($admin) && $zone->status === ZoneStatus::Active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ServiceAreasManage->value);
    }
}
