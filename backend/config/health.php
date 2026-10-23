<?php

return [
    /*
    | Cache key written every minute by `system:heartbeat` (scheduled in routes/console.php).
    */
    'heartbeat_cache_key' => 'system:scheduler:heartbeat',

    /*
    | The scheduler is reported as failing when the last heartbeat is older than this many seconds.
    */
    'heartbeat_max_age_seconds' => (int) env('HEALTH_HEARTBEAT_MAX_AGE', 180),

    /*
    | Queues whose depth the admin health page shows, and the depth at which a queue is reported as degraded.
    */
    'queues' => array_values(array_filter(explode(',', (string) env('HEALTH_QUEUES', 'default,critical')))),
    'queue_degraded_depth' => (int) env('HEALTH_QUEUE_DEGRADED_DEPTH', 500),
    'failed_jobs_degraded_count' => (int) env('HEALTH_FAILED_JOBS_DEGRADED', 1),

    /*
    | An adapter with a recorded failure newer than this many minutes is reported as failing (a later success clears it).
    */
    'adapter_failure_window_minutes' => (int) env('HEALTH_ADAPTER_FAILURE_WINDOW', 15),

    /*
    | Payment webhook rejections / failures inside this window are shown (and degrade the row at the given count).
    */
    'webhook_window_hours' => (int) env('HEALTH_WEBHOOK_WINDOW_HOURS', 24),
    'webhook_degraded_count' => (int) env('HEALTH_WEBHOOK_DEGRADED', 1),

    'socket_timeout_seconds' => (float) env('HEALTH_SOCKET_TIMEOUT', 1.5),
];
