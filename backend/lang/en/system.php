<?php

/*
| System operations (P5.6): integration health, failed queue jobs, app versions, feature flags.
*/
return [
    'enums' => [
        'app' => ['customer' => 'Customer app', 'provider' => 'Provider app'],
        'platform' => ['android' => 'Android', 'ios' => 'iOS'],
        'flag' => ['wallet' => 'Wallet', 'offers' => 'Offers', 'referrals' => 'Referrals', 'loyalty' => 'Loyalty', 'incentives' => 'Provider incentives'],
    ],

    'health' => [
        'menu' => 'Integration health',
        'title' => 'Integration health',
        'subtitle' => 'Infrastructure, queues and every external provider. No secrets are shown here.',
        'refresh' => 'Refresh',
        'overall' => 'Overall status',
        'last_error' => 'Last error',
        'not_configured_hint' => 'No provider is configured. Features that depend on it stay hidden.',
        'groups' => ['infrastructure' => 'Infrastructure', 'adapters' => 'External providers'],
        'areas' => [
            'database' => 'Database', 'redis' => 'Redis', 'queue' => 'Queue', 'failed_jobs' => 'Failed jobs', 'scheduler' => 'Scheduler',
            'reverb' => 'Realtime (Reverb)', 'storage' => 'File storage', 'maps' => 'Maps', 'sms' => 'SMS', 'payments' => 'Payments',
            'push' => 'Push notifications', 'file_scanner' => 'Upload scanner', 'email' => 'Email', 'error_tracker' => 'Error tracking', 'payment_webhooks' => 'Payment webhook failures',
        ],
        'details' => [
            'duration_ms' => 'Response (ms)', 'heartbeat_age_seconds' => 'Last heartbeat (seconds ago)', 'depth' => 'Waiting in :queue',
            'failed' => 'Failed', 'disk' => 'Disk', 'driver' => 'Driver', 'reason' => 'Reason', 'port' => 'Port', 'failures' => 'Failures',
            'window_hours' => 'Window (hours)', 'last_gateway' => 'Last gateway',
        ],
    ],

    'queues' => [
        'title' => 'Failed jobs',
        'subtitle' => ':count failed job(s) waiting for a decision.',
        'notice' => 'Payloads and error messages are not shown because they can hold personal data. A retry needs a reason and is recorded in the audit log.',
        'retry' => 'Retry',
        'retried' => 'The job was put back on its queue.',
        'confirm_title' => 'Retry this job',
        'confirm_message' => 'The job runs again on its original queue. Only retry when the cause is fixed.',
        'fields' => ['job' => 'Job', 'queue' => 'Queue', 'connection' => 'Connection', 'exception' => 'Error type', 'failed_at' => 'Failed at'],
    ],

    'app_versions' => [
        'menu' => 'App versions',
        'title' => 'App versions',
        'subtitle' => 'Minimum version, forced update and store link for each app and platform.',
        'notice' => 'The apps read these values from the public configuration. A forced update shows a screen with the store link, so a link is required.',
        'create' => 'Add app version',
        'edit' => 'Edit app version',
        'saved' => 'App version saved.',
        'fields' => [
            'app' => 'App', 'platform' => 'Platform', 'min_version' => 'Minimum version', 'latest_version' => 'Latest version',
            'force_update' => 'Force update', 'store_url' => 'Store link',
        ],
        'hints' => [
            'min_version' => 'Format 1.2.3. Older apps are told to update.',
            'latest_version' => 'Optional. Shown as the recommended version.',
            'store_url' => 'HTTPS link to the store page of this app.',
            'force_update' => 'When on, apps below the minimum version cannot be used until updated.',
        ],
    ],

    'flags' => [
        'menu' => 'Feature flags',
        'title' => 'Feature flags',
        'subtitle' => 'Switch features on for chosen apps, cities or a share of users.',
        'notice' => 'A feature with no row here follows its legacy setting (wallet and offers) or stays off. The server decides; apps receive the rules in the public configuration.',
        'create' => 'Add feature flag',
        'edit' => 'Edit feature flag',
        'saved' => 'Feature flag saved.',
        'everyone' => 'Everyone',
        'all_cities' => 'All cities',
        'n_cities' => ':count cities',
        'fields' => ['key' => 'Feature', 'enabled' => 'Enabled', 'apps' => 'Apps', 'cities' => 'Cities', 'rollout' => 'Rollout (%)', 'note' => 'Note'],
        'hints' => [
            'enabled' => 'Master switch. Off means off for everybody.',
            'apps' => 'Leave empty for both apps.',
            'cities' => 'Leave empty for all cities.',
            'rollout' => '100 means everybody in the targeted apps and cities. Lower values pick a stable share of users.',
        ],
    ],
];
