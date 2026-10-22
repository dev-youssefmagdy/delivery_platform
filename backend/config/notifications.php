<?php

/*
| Notification platform (P3.6, ADR-132..). Delivery runs on the `notifications` queue.
*/
return [
    // Attempts per delivery (first try included) and the wait before each retry, in seconds.
    'max_attempts' => (int) env('NOTIFICATIONS_MAX_ATTEMPTS', 5),
    'retry_backoff' => [30, 120, 600, 1800],

    // A queued delivery older than this many minutes without an attempt is picked up again by `notifications:retry-overdue`.
    'overdue_after_minutes' => (int) env('NOTIFICATIONS_OVERDUE_AFTER_MINUTES', 5),

    // Inbox entries older than this are pruned (days).
    'retention_days' => (int) env('NOTIFICATIONS_RETENTION_DAYS', 180),

    'inbox_page_size' => 20,
    'inbox_max_page_size' => 50,

    // Admin bell: entries shown in the dropdown.
    'admin_bell_limit' => 8,
];
