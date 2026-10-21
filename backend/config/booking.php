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
];
