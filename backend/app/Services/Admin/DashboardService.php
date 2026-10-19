<?php

namespace App\Services\Admin;

use App\DTOs\Admin\DashboardData;
use App\Models\Admin;
use App\Support\Admin\AdminAccess;
use App\Support\Admin\AdminMenu;

/**
 * Dashboard placeholder (P1.1): no numbers exist yet, so it lists what the admin may reach and which areas
 * become available with later milestones (config admin.dashboard.sections). Real KPIs arrive in P5.3.
 */
final class DashboardService
{
    public function __construct(private readonly AdminMenu $menu) {}

    public function overview(Admin $admin): DashboardData
    {
        $sections = [];

        foreach ((array) config('admin.dashboard.sections', []) as $section) {
            if (is_array($section) && AdminAccess::allows((string) $section['permission'])) {
                $sections[] = ['title' => (string) __((string) $section['title']), 'availableAfter' => (string) __((string) $section['available_after'])];
            }
        }

        return new DashboardData(
            name: $admin->name,
            roleLabels: $admin->loadMissing('roles')->roles->map(static fn ($role) => $role->displayName())->values()->all(),
            isSuperAdmin: $admin->isSuperAdmin(),
            permissionCount: $admin->isSuperAdmin() ? count(\App\Enums\Admin\AdminPermission::cases()) : $admin->getAllPermissions()->count(),
            mfaEnabled: $admin->hasMfaEnabled(),
            menu: $this->menu->visibleGroups(),
            pendingSections: $sections,
        );
    }
}
