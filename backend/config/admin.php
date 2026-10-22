<?php

return [
    /*
    | URL prefix of the admin panel (ADR-01). Change it to obscure the panel URL; route names stay `admin.*`.
    */
    'path' => trim((string) env('ADMIN_PATH', 'admin'), '/') ?: 'admin',

    /*
    | Timezone used to display timestamps in the admin panel until markets carry their own timezone (P1.2).
    | Values are always stored in UTC.
    */
    'display_timezone' => env('ADMIN_DISPLAY_TIMEZONE', 'Africa/Cairo'),

    /*
    | Default currency for money inputs/columns when a record has none (ISO 4217).
    */
    'default_currency' => env('ADMIN_DEFAULT_CURRENCY', 'EGP'),

    /*
    | Admin authentication (ADR-06, P1.1). `mfa.required_roles` lists the AdminRole values whose holders MUST
    | enrol TOTP MFA before using the panel. Empty env value = nobody is forced. Failed logins lock an email+IP
    | pair for `login.lockout_seconds` after `rate_limits.admin-login.per_minute` failures.
    |
    | Since P1.2 `mfa.required_roles`, `login.lockout_seconds` and the rate limits are typed platform settings
    | (SettingKey::AdminMfaRequiredRoles, AdminLoginLockoutSeconds, RateLimit*) edited in Admin > Settings. The values
    | here are only the DEFAULTS used until an admin overrides them (ADR-55).
    */
    'auth' => [
        'mfa' => [
            'required_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env(
                'ADMIN_MFA_REQUIRED_ROLES',
                'super_admin,finance_manager,finance_payout_officer,system_integration_admin',
            ))))),
            'issuer' => env('ADMIN_MFA_ISSUER', env('APP_NAME', 'Delivery Platform')),
            'recovery_codes' => 8,
            'window' => 1,
            'pending_ttl_seconds' => 300,
            'max_attempts' => 5,
        ],
        'login' => [
            'lockout_seconds' => (int) env('ADMIN_LOGIN_LOCKOUT_SECONDS', 900),
        ],
        'password' => [
            'min_length' => 12,
        ],
        'sessions' => [
            'touch_after_seconds' => 60,
        ],
    ],

    /*
    | Dashboard placeholder sections (shown with "available after …" until their milestone ships real data).
    | An entry is listed only to admins holding its permission.
    */
    'dashboard' => [
        'sections' => [
            ['permission' => 'bookings.view', 'title' => 'admin.dashboard.sections.bookings', 'available_after' => 'admin.dashboard.after.bookings'],
            ['permission' => 'drivers.view', 'title' => 'admin.dashboard.sections.providers', 'available_after' => 'admin.dashboard.after.providers'],
            ['permission' => 'finance.view', 'title' => 'admin.dashboard.sections.finance', 'available_after' => 'admin.dashboard.after.finance'],
            ['permission' => 'support.view', 'title' => 'admin.dashboard.sections.support', 'available_after' => 'admin.dashboard.after.support'],
            ['permission' => 'reports.view', 'title' => 'admin.dashboard.sections.reports', 'available_after' => 'admin.dashboard.after.reports'],
        ],
    ],

    /*
    | The "Demo Item" proof module (P0.3). Routes are registered only when enabled AND the environment is
    | local or testing; the menu entry is hidden everywhere else.
    */
    'demo' => [
        'enabled' => (bool) env('ADMIN_DEMO_ENABLED', false),
        'environments' => ['local', 'testing'],
    ],

    /*
    | Mandatory reason length for dangerous actions (<x-admin.confirm-action reason-required>).
    */
    'reason' => [
        'min' => 5,
        'max' => 1000,
    ],

    /*
    | Remote selects (<x-admin.form.remote-select lookup="…">). key => Lookup class. Each lookup is
    | permission-checked and backed by a repository method; unknown keys return 404.
    */
    'lookups' => [
        'countries' => App\Support\Admin\Lookups\CountryLookup::class,
        'admins' => App\Support\Admin\Lookups\AdminLookup::class,
        'service-families' => App\Support\Admin\Lookups\ServiceFamilyLookup::class,
        'service-categories' => App\Support\Admin\Lookups\ServiceCategoryLookup::class,
        'vehicle-categories' => App\Support\Admin\Lookups\VehicleCategoryLookup::class,
        'cities' => App\Support\Admin\Lookups\CityLookup::class,
        'providers' => App\Support\Admin\Lookups\ProviderLookup::class,
        'zones' => App\Support\Admin\Lookups\ZoneLookup::class,
        'customers' => App\Support\Admin\Lookups\CustomerLookup::class,
        'finance-providers' => App\Support\Admin\Lookups\FinanceProviderLookup::class,
        'wallets' => App\Support\Admin\Lookups\WalletLookup::class,
        // @admin-module:lookups
    ],

    'lookup_limit' => 20,

    /*
    | Queued CSV exports (Plan §5.4). Files live on the private disk; download links are signed.
    */
    'exports' => [
        'disk' => env('ADMIN_EXPORTS_DISK', 'local'),
        'directory' => 'exports',
        'link_ttl_minutes' => 60 * 24,
        'retention_days' => 7,
        'chunk' => 500,
    ],

    /*
    | Map inputs/previews (Leaflet). Tiles are fetched by the admin's browser.
    */
    /*
    | Realtime for the admin panel (Echo over Reverb, channel `private-admin.operations`). The browser connects with the
    | public key only; with no key the panel shows "Realtime is not configured" and lists keep their polling refresh.
    */
    'realtime' => [
        'key' => env('REVERB_APP_KEY'),
        'host' => env('ADMIN_REALTIME_HOST', env('REVERB_HOST')),
        'port' => (int) env('ADMIN_REALTIME_PORT', env('REVERB_PORT', 8080)),
        'scheme' => env('ADMIN_REALTIME_SCHEME', env('REVERB_SCHEME', 'http')),
    ],

    'maps' => [
        'tile_url' => env('ADMIN_MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('ADMIN_MAP_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
        'default_center' => [
            'lat' => (float) env('ADMIN_MAP_DEFAULT_LAT', 30.0444),
            'lng' => (float) env('ADMIN_MAP_DEFAULT_LNG', 31.2357),
        ],
        'default_zoom' => (int) env('ADMIN_MAP_DEFAULT_ZOOM', 11),
    ],

    /*
    | Upload limits shown as hints and enforced by FormRequests (kilobytes).
    */
    'uploads' => [
        'image_max_kb' => 2048,
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
