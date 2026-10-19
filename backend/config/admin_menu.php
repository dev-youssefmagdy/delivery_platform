<?php

/*
| Admin sidebar (Plan Appendix D). Groups keep this order; items attach to a group by key. An item is shown
| only when its route exists, the current admin has `permission` (any of, when a list), and the environment is
| listed in `environments` (when set). Labels are translation keys. `active` is a route-name pattern.
| make:admin-module appends items above the marker.
*/
return [
    'groups' => [
        'dashboard' => ['label' => 'admin.menu.groups.dashboard', 'icon' => 'home'],
        'operations' => ['label' => 'admin.menu.groups.operations', 'icon' => 'bolt'],
        'people' => ['label' => 'admin.menu.groups.people', 'icon' => 'users'],
        'catalog' => ['label' => 'admin.menu.groups.catalog', 'icon' => 'squares'],
        'geography' => ['label' => 'admin.menu.groups.geography', 'icon' => 'map'],
        'pricing' => ['label' => 'admin.menu.groups.pricing', 'icon' => 'tag'],
        'marketing' => ['label' => 'admin.menu.groups.marketing', 'icon' => 'megaphone'],
        'finance' => ['label' => 'admin.menu.groups.finance', 'icon' => 'banknotes'],
        'support' => ['label' => 'admin.menu.groups.support', 'icon' => 'lifebuoy'],
        'safety' => ['label' => 'admin.menu.groups.safety', 'icon' => 'shield'],
        'moderation' => ['label' => 'admin.menu.groups.moderation', 'icon' => 'star'],
        'content' => ['label' => 'admin.menu.groups.content', 'icon' => 'document'],
        'reports' => ['label' => 'admin.menu.groups.reports', 'icon' => 'chart'],
        'system' => ['label' => 'admin.menu.groups.system', 'icon' => 'cog'],
        'developer' => ['label' => 'admin.menu.groups.developer', 'icon' => 'code'],
    ],

    'items' => [
        [
            'key' => 'dashboard',
            'group' => 'dashboard',
            'label' => 'admin.menu.items.dashboard',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
            'icon' => 'home',
            'permission' => App\Enums\Admin\AdminPermission::DashboardView,
        ],
        [
            'key' => 'admins',
            'group' => 'system',
            'label' => 'admin.admins.menu',
            'route' => 'admin.admins.index',
            'active' => 'admin.admins.*',
            'icon' => 'users',
            'permission' => App\Enums\Admin\AdminPermission::AdminsView,
        ],
        [
            'key' => 'roles',
            'group' => 'system',
            'label' => 'admin.roles.menu',
            'route' => 'admin.roles.index',
            'active' => 'admin.roles.*',
            'icon' => 'key',
            'permission' => App\Enums\Admin\AdminPermission::RolesView,
        ],
        [
            'key' => 'audit_logs',
            'group' => 'system',
            'label' => 'admin.audit_logs.menu',
            'route' => 'admin.audit-logs.index',
            'active' => 'admin.audit-logs.*',
            'icon' => 'clipboard',
            'permission' => App\Enums\Admin\AdminPermission::AuditLogsView,
        ],
        [
            'key' => 'login_activity',
            'group' => 'system',
            'label' => 'admin.login_activity.menu',
            'route' => 'admin.login-activity.index',
            'active' => 'admin.login-activity.*',
            'icon' => 'lock-closed',
            'permission' => App\Enums\Admin\AdminPermission::LoginActivityView,
        ],
        [
            'key' => 'demo_items',
            'group' => 'developer',
            'label' => 'admin.demo_items.menu',
            'route' => 'admin.demo-items.index',
            'active' => 'admin.demo-items.*',
            'icon' => 'cube',
            'permission' => App\Enums\Admin\AdminPermission::DemoItemsView,
            'environments' => ['local', 'testing'],
        ],
        // @admin-module:menu
    ],
];
