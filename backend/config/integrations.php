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
    'file_scanner' => ['driver' => env('FILE_SCANNER_DRIVER')],

    /*
    | Drivers that simulate success and therefore must never run in staging/production.
    */
    'non_production_drivers' => ['fake', 'log'],

    'non_production_environments' => ['local', 'testing'],
];
