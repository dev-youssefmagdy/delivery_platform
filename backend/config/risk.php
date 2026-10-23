<?php

/*
| Risk signals (P5.2, SRS §15). Thresholds of the scheduled detectors. A signal is an internal, explainable indicator that creates a
| review task: it never suspends anybody by itself, and a low rating is never an input. All values can be tuned per environment.
*/
return [
    // Look-back of every detector, in days.
    'window_days' => (int) env('RISK_WINDOW_DAYS', 30),
    // A dismissed or actioned signal of the same type and subject is not raised again for this many days.
    'cooldown_days' => (int) env('RISK_COOLDOWN_DAYS', 14),
    // Hard cap of signals written per detector run (protects the queue from a bad threshold).
    'max_per_run' => (int) env('RISK_MAX_PER_RUN', 500),

    'reviews' => [
        // A person may appeal a moderation outcome or a low rating within this many days.
        'appeal_window_days' => (int) env('REVIEWS_APPEAL_WINDOW_DAYS', 30),
        // Ratings up to this many stars count as "low" and can be appealed by the person they are about.
        'low_rating_max_stars' => (int) env('REVIEWS_LOW_RATING_MAX_STARS', 2),
    ],

    'cancellation_rate' => [
        'min_bookings' => (int) env('RISK_CANCELLATION_MIN_BOOKINGS', 5),
        'min_rate_percent' => (int) env('RISK_CANCELLATION_MIN_RATE', 50),
    ],
    'no_show' => [
        'min_events' => (int) env('RISK_NO_SHOW_MIN_EVENTS', 3),
    ],
    'offer_spam' => [
        'min_offers' => (int) env('RISK_OFFER_SPAM_MIN_OFFERS', 40),
        'max_accept_percent' => (int) env('RISK_OFFER_SPAM_MAX_ACCEPT', 5),
    ],
    'location_anomaly' => [
        'min_events' => (int) env('RISK_LOCATION_MIN_EVENTS', 5),
    ],
    'account_farm' => [
        'min_accounts_per_device' => (int) env('RISK_FARM_MIN_ACCOUNTS', 3),
    ],
    'payment_dispute' => [
        'min_events' => (int) env('RISK_PAYMENT_DISPUTE_MIN_EVENTS', 3),
    ],
    // Filled by P5.7 (referrals); the detector exists and returns nothing until then.
    'referral_abuse' => [
        'enabled' => false,
    ],
];
