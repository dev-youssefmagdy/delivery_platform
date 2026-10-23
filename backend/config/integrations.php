<?php

/*
| External integration drivers (ADR-12). Every area is reached only through a contract in
| app/Integrations/<Area>/Contracts. The `fake` and `log` drivers are refused at boot outside local/testing
| (IntegrationServiceProvider). `null` means "not configured": features depending on it must stay hidden and
| the health page reports "not configured" — never fake success.
*/
return [
    'sms' => ['driver' => env('SMS_DRIVER')],
    'maps' => ['driver' => env('MAPS_DRIVER')],
    'payments' => ['driver' => env('PAYMENT_DRIVER')],
    // fcm: Firebase Cloud Messaging HTTP v1 (APNs through FCM). `credentials` is the path of the service-account JSON, kept outside the repository.
    'push' => [
        'driver' => env('PUSH_DRIVER'),
        'fcm' => [
            'project_id' => env('FIREBASE_PROJECT_ID'),
            'credentials' => env('FIREBASE_CREDENTIALS'),
        ],
    ],
    // The mailer used for notification emails; `log` / `array` only in local/testing (the same boot guard applies).
    'email' => ['driver' => env('MAIL_MAILER')],
    // Optional error tracking (SRS 23.4). Off unless SENTRY_DSN is set; only the exception, release and correlation id are sent.
    'error_tracker' => [
        'driver' => env('SENTRY_DSN') ? 'sentry' : null,
        'dsn' => env('SENTRY_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'release' => env('SENTRY_RELEASE'),
        'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),
    ],
    'phone_relay' => ['driver' => env('PHONE_RELAY_DRIVER')],
    'file_scanner' => [
        'driver' => env('FILE_SCANNER_DRIVER'),
        'clamav' => [
            'host' => env('CLAMAV_HOST', '127.0.0.1'),
            'port' => (int) env('CLAMAV_PORT', 3310),
            'timeout_seconds' => (int) env('CLAMAV_TIMEOUT_SECONDS', 20),
        ],
    ],

    /*
    | Drivers that simulate success and therefore must never run in staging/production.
    */
    'non_production_drivers' => ['fake', 'log', 'noop', 'sandbox', 'array'],

    'non_production_environments' => ['local', 'testing'],
];
