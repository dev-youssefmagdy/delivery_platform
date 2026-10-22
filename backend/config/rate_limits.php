<?php

/*
| Named rate limiters (Plan §4.4, SRS §20.7). Values are per-minute unless stated. They are registered in
| AppServiceProvider. Since P1.2 these values are only the DEFAULTS of the typed settings SettingKey::RateLimit*
| (edited in Admin > Settings > Rate limits).
*/
return [
    'api' => [
        'per_minute' => (int) env('RATE_LIMIT_API_PER_MINUTE', 120),
    ],

    'otp-request' => [
        // Per phone number and per IP: 3/min and 10/hour each.
        'per_minute' => (int) env('RATE_LIMIT_OTP_REQUEST_PER_MINUTE', 3),
        'per_hour' => (int) env('RATE_LIMIT_OTP_REQUEST_PER_HOUR', 10),
    ],

    'otp-verify' => [
        'per_minute' => (int) env('RATE_LIMIT_OTP_VERIFY_PER_MINUTE', 5),
    ],

    'location' => [
        // Driver location pings (~every 5 s while active).
        'per_minute' => (int) env('RATE_LIMIT_LOCATION_PER_MINUTE', 30),
    ],

    'offers' => [
        'per_minute' => (int) env('RATE_LIMIT_OFFERS_PER_MINUTE', 20),
    ],

    'public-forms' => [
        'per_minute' => (int) env('RATE_LIMIT_PUBLIC_FORMS_PER_MINUTE', 5),
    ],

    'provider-uploads' => [
        // Document and vehicle photo uploads of one provider.
        'per_minute' => (int) env('RATE_LIMIT_PROVIDER_UPLOADS_PER_MINUTE', 20),
    ],

    'payment-webhooks' => [
        // Gateway callbacks per IP: signature-checked, so this only bounds abuse; gateways retry from a few addresses.
        'per_minute' => (int) env('RATE_LIMIT_PAYMENT_WEBHOOKS_PER_MINUTE', 600),
    ],

    'admin-login' => [
        // Per login identifier + IP.
        'per_minute' => (int) env('RATE_LIMIT_ADMIN_LOGIN_PER_MINUTE', 5),
    ],
];
