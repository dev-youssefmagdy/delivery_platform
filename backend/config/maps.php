<?php

/*
| Maps adapter settings (P1.5, ADR-12). The active driver is config('integrations.maps.driver')
| (env MAPS_DRIVER: osrm_nominatim | google | fake [local/testing only]). Credentials only come from the environment.
*/
return [
    'osrm_nominatim' => [
        // Public instances are for development and demos only (usage policies forbid heavy/commercial use).
        'nominatim_url' => env('MAPS_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'osrm_url' => env('MAPS_OSRM_URL', 'https://router.project-osrm.org'),
        // Nominatim requires an identifying User-Agent (and asks for a contact e-mail).
        'user_agent' => env('MAPS_USER_AGENT', 'DeliveryPlatform/1.0'),
        'contact_email' => env('MAPS_CONTACT_EMAIL'),
        // Minimum gap between two Nominatim requests (their policy: at most 1 per second).
        'min_interval_ms' => (int) env('MAPS_NOMINATIM_MIN_INTERVAL_MS', 1100),
        'country_codes' => env('MAPS_COUNTRY_CODES', 'eg'),
        'timeout_seconds' => (int) env('MAPS_TIMEOUT_SECONDS', 8),
    ],

    'google' => [
        'api_key' => env('MAPS_API_KEY'),
        'region' => env('MAPS_GOOGLE_REGION', 'eg'),
        'timeout_seconds' => (int) env('MAPS_TIMEOUT_SECONDS', 8),
    ],

    'cache' => [
        'geocode_ttl' => (int) env('MAPS_CACHE_GEOCODE_TTL', 86400),
        'reverse_ttl' => (int) env('MAPS_CACHE_REVERSE_TTL', 86400),
        'search_ttl' => (int) env('MAPS_CACHE_SEARCH_TTL', 600),
        'route_ttl' => (int) env('MAPS_CACHE_ROUTE_TTL', 600),
    ],

    'search' => [
        'min_query_length' => 2,
        'max_query_length' => 120,
        'default_limit' => 8,
        'max_limit' => 15,
    ],

    'route' => [
        'max_stops' => 5,
    ],
];
