<?php

return [
    'saved_places' => [
        // Total saved places per customer (home + work + others).
        'max' => (int) env('GEO_SAVED_PLACES_MAX', 20),
    ],

    // Demo cities/zones (clearly labelled) are seeded only outside production.
    'seed_demo' => filter_var(env('GEO_SEED_DEMO', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOLEAN),
];
