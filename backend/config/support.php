<?php

/*
| Booking chat and support (P3.7, SRS §14.2, §14.3). Every value has a safe default and can be tuned per environment.
*/
return [
    'chat' => [
        'enabled' => (bool) env('CHAT_ENABLED', true),
        // Minutes after a trip ended (completed or cancelled after a provider was selected) during which both sides may still write.
        'window_after_close_minutes' => (int) env('CHAT_WINDOW_AFTER_CLOSE_MINUTES', 120),
        'max_chars' => 1000,
        // One image per message; the inspector re-encodes it to JPEG.
        'attachment_max_kilobytes' => (int) env('CHAT_ATTACHMENT_MAX_KB', 5120),
        'attachment_max_edge' => 1600,
        'attachment_min_edge' => 50,
        'attachment_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        // Messages are removed this many days after the trip ended; a booking with an open ticket keeps its chat until the ticket is finished.
        'retention_days' => (int) env('CHAT_RETENTION_DAYS', 180),
        // Messages one person may send per minute (rate limit key).
        'per_minute' => (int) env('CHAT_PER_MINUTE', 30),
        'page_size' => 50,
        // Phone numbers in a message body are masked on the way out (SRS §14.2).
        'mask_phone_numbers' => true,
    ],

    'tickets' => [
        'max_open_per_user' => (int) env('SUPPORT_MAX_OPEN_PER_USER', 20),
        'subject_max_chars' => 160,
        'body_max_chars' => 4000,
        'attachments_per_message' => 3,
        'attachment_max_kilobytes' => (int) env('SUPPORT_ATTACHMENT_MAX_KB', 8192),
        'attachment_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        'attachment_max_edge' => 2400,
        // A user reply on a resolved ticket reopens it for this many days; after that a new ticket is needed.
        'reopen_days' => (int) env('SUPPORT_REOPEN_DAYS', 7),
        // Resolved tickets close by themselves after this many days.
        'auto_close_days' => (int) env('SUPPORT_AUTO_CLOSE_DAYS', 14),
        'new_per_day' => (int) env('SUPPORT_NEW_PER_DAY', 10),
    ],

    // Minutes until the first response is due, by priority (SLA).
    'sla_minutes' => [
        'low' => 2880,
        'normal' => 1440,
        'high' => 480,
        'urgent' => 60,
    ],
];
