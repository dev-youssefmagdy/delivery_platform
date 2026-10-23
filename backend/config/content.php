<?php

return [
    /*
    | Public read side (website and apps): how long a cached payload may be reused. A content change also bumps the cache
    | version immediately, so this only bounds banner windows and scheduled publishing.
    */
    'cache_ttl_seconds' => (int) env('CONTENT_CACHE_TTL', 60),

    /*
    | Media library (public disk). Uploads are validated as raster images, re-encoded (metadata and any appended data
    | are dropped) and resized into the listed widths.
    */
    'media' => [
        'disk' => env('CONTENT_MEDIA_DISK', 'public'),
        'directory' => 'content',
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'min_dimension' => 32,
        'max_dimension' => 6000,
        'max_pixels' => 24_000_000,
        'variants' => [320, 960, 1600],
        'quality' => 82,
    ],

    /*
    | Publish scheduling: the earliest a schedule may be set (seconds from now).
    */
    'schedule_min_lead_seconds' => 60,
];
