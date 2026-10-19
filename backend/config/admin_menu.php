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
