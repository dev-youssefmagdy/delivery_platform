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
    | Temporary scaffolding until admin authentication lands (P1.1): in the `local` environment only, guests
    | are granted every admin ability so the UI kit can be exercised in a browser. Never active in any other
    | environment (enforced in AllowLocalGuestAccess). P1.1 removes this switch.
    */
    'local_guest_access' => (bool) env('ADMIN_LOCAL_GUEST_ACCESS', false),

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
