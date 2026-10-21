<?php

/*
| Live driver location (SRS §10.3). Seconds between position samples the app is asked to send per tracking state.
| The server tells the app its current state, so the frequency can change without an app release.
*/
return [
    'intervals' => [
        'off' => 0,
        'idle' => (int) env('LOCATION_INTERVAL_IDLE', 30),
        'to_pickup' => (int) env('LOCATION_INTERVAL_TO_PICKUP', 10),
        'active' => (int) env('LOCATION_INTERVAL_ACTIVE', 5),
    ],

    // Largest batch of buffered samples one request may carry.
    'max_batch' => (int) env('LOCATION_MAX_BATCH', 20),

    // A sample older than this is accepted into the history but never moves the live position.
    'max_sample_age_seconds' => (int) env('LOCATION_MAX_SAMPLE_AGE', 600),

    // A sample timestamped further ahead than this is flagged.
    'clock_skew_seconds' => (int) env('LOCATION_CLOCK_SKEW', 60),

    // Moving this far from the position where the city was last resolved triggers a new zone lookup.
    'city_recheck_meters' => (int) env('LOCATION_CITY_RECHECK_METERS', 500),

    // History retention (ADR-83): rows not tied to a booking, and rows of a booking (kept longer for disputes).
    'retention_days' => (int) env('LOCATION_RETENTION_DAYS', 30),
    'booking_retention_days' => (int) env('LOCATION_BOOKING_RETENTION_DAYS', 90),

    // Geo index: 'redis' (GEOADD/GEOSEARCH) or 'database' (tests and local development without Redis).
    'index' => env('PROVIDER_GEO_INDEX', 'redis'),
];
