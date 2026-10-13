<?php

/*
| Labels for every App\Enums case: enums.{snake_enum}.{value}.
*/
return [
    'badge_color' => [
        'neutral' => 'Neutral',
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
        'danger' => 'Danger',
        'primary' => 'Primary',
    ],

    'locale' => [
        'ar' => 'العربية',
        'en' => 'English',
    ],

    'actor_type' => [
        'customer' => 'Customer',
        'provider' => 'Provider',
        'admin' => 'Admin',
        'system' => 'System',
    ],

    'platform' => [
        'android' => 'Android',
        'ios' => 'iOS',
        'web' => 'Web',
    ],

    'audit_action' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'status_changed' => 'Status changed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'reactivated' => 'Reactivated',
        'published' => 'Published',
        'exported' => 'Exported',
        'viewed' => 'Viewed',
        'logged_in' => 'Logged in',
        'logged_out' => 'Logged out',
        'login_failed' => 'Login failed',
        'overridden' => 'Overridden',
    ],

    'health_status' => [
        'ok' => 'Operational',
        'degraded' => 'Degraded',
        'failing' => 'Failing',
        'not_configured' => 'Not configured',
    ],
];
