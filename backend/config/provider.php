<?php

/*
| Provider onboarding (P2.1). Document rules per document type live in the database (document_types /
| document_requirements); this file only holds technical limits and defaults.
*/
return [
    // Applicants must be at least this old (SRS §7.1: date of birth where required).
    'minimum_age' => 18,

    // DEMO providers (approved, pending, rejected, expired document) for development and demos. Never in production.
    'seed_demo' => filter_var(env('PROVIDER_SEED_DEMO', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOL),

    'documents' => [
        // Private disk of documents and vehicle photos (ADR-11).
        'disk' => env('PROVIDER_DOCUMENTS_DISK', 'private'),
        'directory' => 'providers',
        // Signed URLs for viewing/downloading a file are valid this long.
        'url_ttl_minutes' => 5,
        // Hard cap, whatever a document type allows.
        'max_kilobytes' => 10240,
        // Images are decoded (decompression-bomb guard), re-encoded as JPEG and stripped of metadata.
        'image_min_edge' => 200,
        'image_max_edge' => 2400,
        'image_max_pixels' => 40_000_000,
        // MIME types a document type may allow; the extension must agree with the sniffed MIME type.
        'mime_extensions' => [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
        ],
        // Days before expiry at which the provider is reminded (descending).
        'expiry_reminder_days' => [30, 7],
    ],

    'vehicles' => [
        'min_photos' => 2,
        'max_photos' => 8,
        'photo_max_kilobytes' => 8192,
        'photo_max_edge' => 1600,
        'min_year' => 1980,
        'max_per_provider' => 10,
    ],
];
