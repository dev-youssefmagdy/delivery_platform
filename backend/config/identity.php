<?php

/*
| Mobile identity (P1.3). OTP length / TTL / cooldown / attempts are typed settings (SettingKey::Otp*), not here.
*/
return [
    /*
    | Bearer token lifetime. Clients re-issue (POST /auth/refresh) before it ends; a revoked or expired token is a 401.
    */
    'token_ttl_days' => (int) env('AUTH_TOKEN_TTL_DAYS', 90),

    /*
    | Fixed OTP for listed test numbers (SRS §28). Honoured only in the environments of
    | config('integrations.non_production_environments'); TestOtpGuard refuses to boot anywhere else while any of
    | these values is set, and OtpService re-checks the environment before using them.
    */
    'test_otp' => [
        'enabled' => filter_var(env('OTP_TEST_ENABLED', false), FILTER_VALIDATE_BOOL),
        'code' => env('OTP_TEST_CODE') ?: null,
        'phones' => array_values(array_filter(array_map('trim', explode(',', (string) env('OTP_TEST_PHONES', ''))))),
    ],

    'photo' => [
        'disk' => env('IDENTITY_PHOTO_DISK', 'local'),
        'max_kilobytes' => 5120,
        'max_edge_pixels' => 1024,
        'url_ttl_minutes' => 15,
    ],

    /*
    | A user's last_seen_at is refreshed at most this often.
    */
    'last_seen_interval_seconds' => 300,
];
