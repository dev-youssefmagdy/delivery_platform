<?php

/*
| Safety centre (P5.1, SRS §13, §16.10). Every value has a safe default and can be tuned per environment.
*/
return [
    // Minutes until a safety officer must have acknowledged a case, by priority.
    'sla_minutes' => [
        'urgent' => (int) env('SAFETY_SLA_URGENT_MINUTES', 5),
        'high' => (int) env('SAFETY_SLA_HIGH_MINUTES', 15),
        'normal' => (int) env('SAFETY_SLA_NORMAL_MINUTES', 60),
    ],

    // Local emergency numbers shown by the apps. The platform does NOT call them and does not dispatch anybody (SRS §13.2): the
    // person must call. Keyed by ISO country code of the market; `default` is used when the market has no entry.
    'emergency_numbers' => [
        'EG' => [
            ['key' => 'police', 'number' => '122'],
            ['key' => 'ambulance', 'number' => '123'],
            ['key' => 'fire', 'number' => '180'],
            ['key' => 'tourist_police', 'number' => '126'],
        ],
        'default' => [
            ['key' => 'police', 'number' => '112'],
        ],
    ],

    'sos' => [
        // Seconds the button must be held; the apps read it from /config/app.
        'hold_seconds' => (int) env('SAFETY_SOS_HOLD_SECONDS', 2),
        // A position older than this is shown to staff as stale.
        'location_max_age_seconds' => 300,
        // Jobs an SOS may be raised on: a provider is assigned and the job is not finished.
        'counterpart_location_max_age_seconds' => 300,
    ],

    'reports' => [
        // Non-urgent reports per person and hour. An SOS is never counted and never refused (repeated presses join the open case).
        'per_hour' => (int) env('SAFETY_REPORTS_PER_HOUR', 6),
        'narrative_max_chars' => 4000,
        'update_max_chars' => 2000,
    ],

    'trusted_contacts' => [
        'max_per_user' => (int) env('SAFETY_TRUSTED_CONTACTS_MAX', 5),
        'name_max_chars' => 80,
    ],

    'trip_share' => [
        // Trip-link messages a person may send per booking and hour.
        'per_hour' => (int) env('SAFETY_TRIP_SHARE_PER_HOUR', 6),
    ],

    'attachments' => [
        'per_incident' => 10,
        'max_kilobytes' => (int) env('SAFETY_ATTACHMENT_MAX_KB', 8192),
        'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        'per_minute' => (int) env('SAFETY_ATTACHMENTS_PER_MINUTE', 10),
    ],

    // Signed evidence links last this long and are streamed only after the policy check; every stream is audited.
    'evidence_url_ttl_minutes' => (int) env('SAFETY_EVIDENCE_URL_TTL_MINUTES', 5),

    // Cases and evidence are kept this long after resolution unless a legal hold is set.
    'retention_days' => (int) env('SAFETY_RETENTION_DAYS', 730),
];
