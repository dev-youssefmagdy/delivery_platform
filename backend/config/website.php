<?php

/*
| Public website (P5.5). Brand, contact details and form protection. Nothing here is a secret.
*/
return [
    'brand' => [
        'name' => env('WEBSITE_BRAND_NAME', 'Tawsila'),
        'theme_color' => '#0f766e',
    ],

    // Shown in the footer and the Organization JSON-LD. Empty values are simply not rendered.
    'contact' => [
        'support_email' => env('WEBSITE_SUPPORT_EMAIL'),
        'support_phone' => env('WEBSITE_SUPPORT_PHONE'),
        // Local numbers (01001234567) in website forms are read with this calling code.
        'default_phone_code' => env('WEBSITE_DEFAULT_PHONE_CODE', '+20'),
        'placeholder_email' => 'website-contact@system.invalid',
        'max_per_email_per_day' => (int) env('WEBSITE_CONTACT_MAX_PER_EMAIL_PER_DAY', 3),
    ],

    // Extra image origins the Content-Security-Policy of the website and the admin panel allows (for example a CDN or an object storage
    // host that serves CMS media). Comma separated origins in CSP_EXTRA_IMG_SRC; empty = the app's own origin only.
    'csp_img_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('CSP_EXTRA_IMG_SRC', ''))))),

    'social' => [
        'facebook' => env('WEBSITE_SOCIAL_FACEBOOK'),
        'instagram' => env('WEBSITE_SOCIAL_INSTAGRAM'),
        'x' => env('WEBSITE_SOCIAL_X'),
        'linkedin' => env('WEBSITE_SOCIAL_LINKEDIN'),
        'youtube' => env('WEBSITE_SOCIAL_YOUTUBE'),
    ],

    'forms' => [
        // Time-trap: a form submitted sooner than this after it was rendered, or later than the max age, is refused.
        'min_fill_seconds' => (int) env('WEBSITE_FORM_MIN_FILL_SECONDS', 3),
        'max_age_minutes' => (int) env('WEBSITE_FORM_MAX_AGE_MINUTES', 120),
        // Validate-before-submit calls (Precognition) have their own, more generous limiter; the real submit uses `public-forms`.
        'precognition_per_minute' => (int) env('WEBSITE_PRECOGNITION_PER_MINUTE', 40),
    ],

    // Sitemap/robots cache (seconds). A content change is visible after at most this long.
    'sitemap_ttl_seconds' => (int) env('WEBSITE_SITEMAP_TTL', 300),
];
