<?php

/*
| Online payments (P3.2, SRS §12). The active gateway is chosen by PAYMENT_DRIVER (config/integrations.php):
| `sandbox` (local/testing only, refused at boot elsewhere) or `paymob`. Secrets come from the environment only.
*/
return [
    // Where the processor sends the customer back after the hosted payment page. The apps never treat this return as
    // proof of payment: only the verified webhook or a gateway query marks a payment paid (Plan I8).
    'return_url' => env('PAYMENT_RETURN_URL'),

    'webhook' => [
        // Replay window for gateways that sign a timestamp (the sandbox does; Paymob does not and relies on event ids).
        'tolerance_seconds' => (int) env('PAYMENT_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],

    'reconcile' => [
        // payments:reconcile looks back this far and ignores payments younger than the grace period.
        'window_hours' => (int) env('PAYMENT_RECONCILE_WINDOW_HOURS', 48),
        'grace_minutes' => (int) env('PAYMENT_RECONCILE_GRACE_MINUTES', 10),
    ],

    'sandbox' => [
        'secret' => env('PAYMENT_SANDBOX_SECRET'),
        'delay_seconds' => (int) env('PAYMENT_SANDBOX_DELAY_SECONDS', 2),
        // succeed | fail | requires_action | silent (no webhook: exercises timeouts)
        'outcome' => env('PAYMENT_SANDBOX_OUTCOME', 'succeed'),
        'refund_outcome' => env('PAYMENT_SANDBOX_REFUND_OUTCOME', 'succeed'),
    ],

    'paymob' => [
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'api_key' => env('PAYMOB_API_KEY'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'card_integration_id' => env('PAYMOB_CARD_INTEGRATION_ID'),
        'wallet_integration_id' => env('PAYMOB_WALLET_INTEGRATION_ID'),
        // Public HTTPS URL of POST /api/v1/payment-gateways/paymob/webhook.
        'notification_url' => env('PAYMOB_NOTIFICATION_URL'),
    ],
];
