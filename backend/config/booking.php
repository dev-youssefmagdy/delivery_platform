<?php

/*
| Booking creation (P2.3). Business limits that admins tune live in platform settings (SettingKey); what is here is
| technical or deployment-level.
*/
return [
    // Payment methods that can actually be settled today. Cash only until the gateway milestone (P3.2) enables more;
    // a category may list more methods in its configuration, but the intersection is what customers can pick.
    'enabled_payment_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('BOOKING_PAYMENT_METHODS', 'cash'))))),

    // Absolute ceiling for a customer-proposed fare in minor units when neither the category nor the zone sets one.
    'hard_max_fare_minor' => (int) env('BOOKING_HARD_MAX_FARE_MINOR', 100_000_000),

    // Characters of the public booking code (no 0/O/1/I/L to survive being read aloud or typed).
    'code' => [
        'prefix' => 'BK-',
        'alphabet' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'length' => 6,
        'max_attempts' => 8,
    ],

    // Searches that ended / scheduled bookings that are due are processed in batches of this size per sweep.
    'sweep_batch' => (int) env('BOOKING_SWEEP_BATCH', 200),

    // Longest free-text field a customer may attach to a booking address.
    'address_line_max' => 255,

    // Negotiation (P2.4).
    'offers' => [
        // Whether the offer card shows the plate before the customer has chosen (local privacy policy, SRS §6.5).
        'show_plate_before_selection' => (bool) env('BOOKING_OFFER_SHOW_PLATE', true),
        // Offers whose time ran out are closed in batches of this size per `offers:expire` sweep.
        'sweep_batch' => (int) env('BOOKING_OFFER_SWEEP_BATCH', 200),
        // Provider-stated arrival estimate bounds, in minutes.
        'eta_max_minutes' => 120,
        // Largest structured scope a counter-offer may carry (JSON bytes) and its allowed top-level keys.
        'scope_max_bytes' => 4096,
        // Digits of the trip PIN the customer shows the driver at pickup (P2.5 verifies it).
        'pin_digits' => 4,
    ],

    // Trip lifecycle (P2.5).
    'trip' => [
        // An arrival reported farther than this from the pickup (metres) is stored but marked unverified: it never
        // triggers a fee on its own (SRS §8.4: no automatic penalty from a single GPS signal).
        'arrival_radius_m' => (int) env('BOOKING_ARRIVAL_RADIUS_M', 300),
        // Positions older than this (seconds) are not used as arrival evidence when the app sends none.
        'evidence_max_age_seconds' => 120,
        // Reports one person may file per booking per day (abuse limit).
        'reports_per_day' => 5,
        // Longest free text of a report, a rating comment and a cancellation note.
        'report_max_chars' => 2000,
        'comment_max_chars' => 1000,
        'cancellation_note_max_chars' => 255,
        // Public trip-share page: the provider position is hidden when older than this (seconds).
        'share_location_max_age_seconds' => 180,
    ],

    // What applies when no cancellation policy covers a category: a short free window after selection, no fees, every
    // reason allowed. Admins configure real policies (Cancellation policies); a booking snapshots whichever applied.
    'cancellation_defaults' => [
        'free_grace_seconds' => 120,
        'free_wait_seconds' => 300,
    ],
];
