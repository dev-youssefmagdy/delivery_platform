<?php

/*
| Data retention (P6.2). Days are measured from the row's own timestamp. Locations, chat, notifications inbox, exports, OTP
| challenges, idempotency keys and safety evidence keep their own settings (location.php, support.php, notifications.php,
| admin.php, models, safety.php); this file holds the rest, run by `retention:run`. Ledger, journal, payment, refund, payout and
| booking rows are NEVER deleted by any job (see docs/PERFORMANCE.md, archive strategy).
*/
return [
    // Share tokens are useless once expired or revoked; keep a short tail for support.
    'share_links_days' => (int) env('RETENTION_SHARE_LINKS_DAYS', 7),

    // Rejected webhook rows (bad signature) carry attacker-controlled payloads and no business value.
    'webhook_rejected_days' => (int) env('RETENTION_WEBHOOK_REJECTED_DAYS', 30),
    // Accepted webhook rows keep their hash, outcome and references forever (payment audit) but lose the raw payload.
    'webhook_payload_days' => (int) env('RETENTION_WEBHOOK_PAYLOAD_DAYS', 90),

    'login_events_days' => (int) env('RETENTION_LOGIN_EVENTS_DAYS', 365),
    'admin_sessions_days' => (int) env('RETENTION_ADMIN_SESSIONS_DAYS', 30),

    // Per-channel delivery attempts (the inbox itself uses notifications.retention_days).
    'notification_deliveries_days' => (int) env('RETENTION_NOTIFICATION_DELIVERIES_DAYS', 180),
    'coupon_attempts_days' => (int) env('RETENTION_COUPON_ATTEMPTS_DAYS', 180),

    'chunk' => 2000,
    'max_chunks_per_step' => 50,
];
