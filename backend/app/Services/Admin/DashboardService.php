<?php

namespace App\Services\Admin;

use App\DTOs\Admin\DashboardData;
use App\Models\Admin;
use App\Support\Admin\AdminMenu;

/**
 * The signed-in admin's own summary on the dashboard (name, roles, permission count, MFA, quick links). The KPIs and charts are built
 * by Reporting\OperationsDashboardService (P5.3).
 */
final class DashboardService
{
    public function __construct(private readonly AdminMenu $menu) {}

    public function overview(Admin $admin): DashboardData
    {
        return new DashboardData(
            name: $admin->name,
            roleLabels: $admin->loadMissing('roles')->roles->map(static fn ($role) => $role->displayName())->values()->all(),
            isSuperAdmin: $admin->isSuperAdmin(),
            permissionCount: $admin->isSuperAdmin() ? count(\App\Enums\Admin\AdminPermission::cases()) : $admin->getAllPermissions()->count(),
            mfaEnabled: $admin->hasMfaEnabled(),
            menu: $this->menu->visibleGroups(),
        );
    }
}
