<?php

/*
| Reputation and abuse (P5.2): enum labels, API messages and the admin Reviews / Risk signals modules.
*/
return [
    'enums' => [
        'report_reason' => ['abusive' => 'Abusive or offensive', 'spam' => 'Spam', 'irrelevant' => 'Not about this trip', 'false_info' => 'False information', 'personal_info' => 'Shares personal information', 'other' => 'Other'],
        'report_status' => ['open' => 'Open', 'actioned' => 'Review hidden', 'dismissed' => 'Dismissed'],
        'moderation_action' => ['hide' => 'Hidden', 'restore' => 'Restored', 'review' => 'Marked under review'],
        'appeal_grounds' => ['hidden_review' => 'My review was hidden', 'unfair_rating' => 'Unfair low rating'],
        'appeal_status' => ['open' => 'Open', 'upheld' => 'Upheld', 'rejected' => 'Rejected'],
        'subject' => ['customer' => 'Customer', 'provider' => 'Provider'],
        'type' => [
            'cancellation_rate' => 'High cancellation rate', 'no_show' => 'Repeated no-shows', 'offer_spam' => 'Offer spam', 'location_anomaly' => 'Location anomalies',
            'referral_abuse' => 'Referral abuse', 'account_farm' => 'Shared device across accounts', 'payment_dispute' => 'Payment disputes',
        ],
        'risk_action' => ['suspend' => 'Suspend', 'restrict' => 'Restrict', 'dismiss' => 'Dismiss', 'escalate' => 'Escalate'],
        'status' => ['open' => 'Open', 'escalated' => 'Escalated', 'dismissed' => 'Dismissed', 'actioned' => 'Action taken'],
    ],

    'appeal_outcome' => ['upheld' => 'your appeal was accepted', 'rejected' => 'your appeal was not accepted'],

    'risk' => ['auto_closed' => 'Closed automatically: the indicator no longer meets the threshold.'],

    'api' => [
        'report_received' => 'Thank you. Your report was sent to our moderation team.',
        'appeal_received' => 'Your appeal was received. We will review it and tell you the outcome.',
    ],

    'admin' => [
        'unnamed' => 'Unnamed',
        'system' => 'System',
        'open_customer' => 'Open customer',
        'open_provider' => 'Open provider',
        'customer_risk_intro' => 'Internal risk signals about this customer. They are review tasks, never shown to the customer and never an automatic sanction.',
        'provider_risk_intro' => 'Internal risk signals about this provider. They are review tasks, never shown to the provider and never an automatic sanction.',
        'tabs' => [
            'reviews' => 'Reviews', 'reports' => 'Reports', 'appeals' => 'Appeals', 'risk' => 'Risk',
            'reports_intro' => 'Reviews people flagged. Open one to decide; a report never changes the average by itself.',
            'appeals_intro' => 'People contesting a hidden review or a low rating. Open one to decide.',
        ],
        'fields' => [
            'rater' => 'Written by', 'ratee' => 'About', 'reports' => 'Open reports', 'reason' => 'Reason', 'reporter' => 'Reported by', 'status' => 'Status',
            'grounds' => 'Grounds', 'appellant' => 'Appealed by', 'type' => 'Signal', 'subject' => 'Subject', 'score' => 'Score', 'detected' => 'Detected',
            'evaluated' => 'Last evaluated', 'stars' => 'Stars', 'reported_only' => 'Reported only', 'ratee_type' => 'About a', 'subject_type' => 'Subject type',
            'min_score' => 'Minimum score', 'moderated_at' => 'Last moderated', 'escalated_at' => 'Escalated', 'resolved_at' => 'Resolved', 'resolution' => 'Resolution',
            'decision' => 'Decision',
        ],
        'actions' => [
            'hide' => 'Hide review', 'restore' => 'Restore review', 'under_review' => 'Mark under review', 'dismiss_report' => 'Dismiss report',
            'uphold' => 'Uphold appeal', 'reject' => 'Reject appeal',
        ],
        'reviews' => [
            'menu' => 'Reviews', 'title' => 'Reviews and moderation', 'subtitle' => ':reports open reports, :appeals open appeals',
            'intro' => 'Ratings written by customers and providers. Moderation changes visibility only: hidden and under-review ratings do not count in the public average, and nobody can edit what a person wrote.',
            'show_title' => 'Review', 'not_counted' => 'This review is not counted in the public average.', 'the_review' => 'The review',
            'read_only' => 'Read only. Administrators cannot edit a review, only hide or restore it with a reason.', 'history' => 'Moderation history',
            'no_reports' => 'Nobody reported this review.', 'no_appeals' => 'No appeals.', 'note' => 'Note: :note',
        ],
        'risk' => [
            'menu' => 'Risk signals', 'title' => 'Risk signals', 'subtitle' => ':active signals waiting for review', 'show_title' => 'Risk signal',
            'internal_notice' => 'Internal use only. A signal is a reason to look, not a verdict: it never suspends anyone by itself and a low rating is never an input.',
            'evidence' => 'Evidence', 'evidence_hint' => 'The facts the check counted. Open the customer or provider for the full picture before acting.',
        ],
        'confirm' => [
            'hide_title' => 'Hide this review?', 'hide_message' => 'The review stops counting in the average and is no longer shown. The writer is told and can appeal. The text is kept unchanged.',
            'restore_title' => 'Restore this review?', 'restore_message' => 'The review counts in the average again.',
            'under_review_title' => 'Mark under review?', 'under_review_message' => 'The review does not count in the average while staff look at it.',
            'dismiss_report_title' => 'Dismiss this report?', 'dismiss_report_message' => 'The review stays as it is.',
            'uphold_title' => 'Uphold this appeal?', 'uphold_message' => 'A hidden review is restored; an unfair low rating is hidden. The person is told the outcome.',
            'reject_title' => 'Reject this appeal?', 'reject_message' => 'Nothing changes on the review. The person is told the outcome.',
            'suspend_title' => 'Suspend this account?', 'suspend_message' => 'Temporary suspension through the account module. Give the reason and an optional end. A permanent decision is taken separately in the provider module.',
            'suspend_expiry_hint' => 'Leave empty to suspend until someone reactivates the account.',
            'restrict_title' => 'Restrict this customer?', 'restrict_message' => 'The customer keeps access but is limited, as in the customers module.',
            'dismiss_title' => 'Dismiss this signal?', 'dismiss_message' => 'No action is needed. It will not be raised again for a while unless the pattern persists after the cooling-off period.',
            'escalate_title' => 'Escalate this signal?', 'escalate_message' => 'Marks it for a senior review and notifies the risk team.',
        ],
        'done' => [
            'hidden' => 'The review was hidden.', 'restored' => 'The review was restored.', 'under_review' => 'The review is under review.',
            'report_dismissed' => 'The report was dismissed.', 'appeal_decided' => 'The appeal was decided.', 'suspended' => 'The account was suspended.',
            'restricted' => 'The customer was restricted.', 'dismissed' => 'The signal was dismissed.', 'escalated' => 'The signal was escalated.',
        ],
    ],
];
