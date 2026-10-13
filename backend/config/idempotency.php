<?php

return [
    /*
    | How long a stored response can be replayed for the same (actor, route, Idempotency-Key).
    */
    'ttl_hours' => (int) env('IDEMPOTENCY_TTL_HOURS', 24),

    /*
    | Cache store providing atomic locks that block concurrent requests with the same key. Must be shared by
    | all app servers in production (redis).
    */
    'lock_store' => env('IDEMPOTENCY_LOCK_STORE', 'redis'),

    /*
    | Upper bound for how long a single request may hold the lock.
    */
    'lock_seconds' => (int) env('IDEMPOTENCY_LOCK_SECONDS', 30),
];
