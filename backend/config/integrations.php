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
    'push' => ['driver' => env('PUSH_DRIVER')],
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
    'non_production_drivers' => ['fake', 'log', 'noop'],

    'non_production_environments' => ['local', 'testing'],
];
