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
];
