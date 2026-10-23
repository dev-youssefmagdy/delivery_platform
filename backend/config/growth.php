<?php

use App\Enums\Risk\RiskSignalType;

/*
| Growth features (P5.7): referrals, loyalty, provider incentives. Everything is gated by the feature flags `referrals`, `loyalty`
| and `incentives` (default OFF, ADR-197). The fraud thresholds only HOLD a reward for manual review; nothing here punishes anyone.
*/
return [
    'code_length' => (int) env('GROWTH_CODE_LENGTH', 8),
    // Base of the shareable link; the code is appended. The website does not have a /r page yet: the link opens the app-download page.
    'link_base' => env('GROWTH_LINK_BASE', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/r'),
    'fraud' => [
        // More than this many referrals by one referrer inside the window is a velocity flag.
        'velocity_max' => (int) env('GROWTH_VELOCITY_MAX', 5),
        'velocity_window_hours' => (int) env('GROWTH_VELOCITY_WINDOW_HOURS', 24),
        // Phones that agree except for the last N digits look like one person registering several numbers.
        'phone_pattern_ignore_last_digits' => (int) env('GROWTH_PHONE_PATTERN_DIGITS', 3),
        // A provider with an open signal of these types does not get a quest bonus automatically: the payout waits for a person.
        'incentive_hold_signal_types' => [RiskSignalType::AccountFarm->value, RiskSignalType::LocationAnomaly->value, RiskSignalType::ReferralAbuse->value],
    ],
    'expiry_batch' => (int) env('GROWTH_EXPIRY_BATCH', 200),
];
