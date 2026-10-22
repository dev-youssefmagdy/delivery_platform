<?php

/*
| Courier / parcel delivery (P4.1, SRS §5.1-C, §7.5). Tunables of proofs, the recipient code and failed attempts.
| Categories decide WHICH proofs a delivery needs (`delivery_proof_types`, `recipient_otp_required`); these values only
| bound how they are collected.
*/
return [
    'recipient_otp' => [
        'length' => (int) env('COURIER_OTP_LENGTH', 6),
        'max_attempts' => (int) env('COURIER_OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('COURIER_OTP_RESEND_COOLDOWN', 60),
        'max_resends' => (int) env('COURIER_OTP_MAX_RESENDS', 3),
    ],

    'proofs' => [
        // Pictures per proof type and stage.
        'max_photos_per_stage' => 5,
        'max_kilobytes' => 5120,
        'max_edge' => 1600,
        // Declared and measured weight may differ by this share before the proof is flagged for review (never blocks).
        'weight_tolerance_percent' => 25,
        // What a return-to-sender leg must show instead of the category's delivery proofs.
        'return_proof_types' => ['photo'],
        'checklist_items' => ['package_sealed', 'package_undamaged', 'label_matches', 'recipient_checked'],
    ],

    'attempts' => [
        // After this many failed attempts a retry is no longer offered: return to sender or operations.
        'max_per_booking' => (int) env('COURIER_MAX_DELIVERY_ATTEMPTS', 3),
        'reason_max_chars' => 500,
        // An attempt nobody resolved (customer, provider) is handed to operations after this long.
        'escalate_after_minutes' => (int) env('COURIER_ESCALATE_AFTER_MINUTES', 30),
    ],

    // Signed URLs of proof pictures live this long.
    'proof_url_ttl_minutes' => 5,
];
