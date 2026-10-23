<?php

/*
| Growth (P5.7): referrals, loyalty, provider incentives. Admin labels, enum labels and the short texts of the app responses.
*/
return [
    'saved' => 'Saved.',
    'moved' => 'Updated.',
    'budget_adjusted' => 'Budget updated.',

    'fields' => [
        'name' => 'Name (Arabic)', 'name_en' => 'Name (English)', 'audience' => 'Audience', 'status' => 'Status', 'campaign' => 'Campaign',
        'starts_at' => 'Starts', 'ends_at' => 'Ends', 'budget' => 'Total budget', 'budget_used' => 'Spent / budget', 'min_order' => 'Minimum order value',
        'categories' => 'Service categories', 'cities' => 'Cities', 'empty_means_all' => 'Leave empty for all.', 'all' => 'All', 'created' => 'Created',
        'flags' => 'Review flags', 'flagged' => 'Flagged only', 'active' => 'Active', 'status_reason' => 'Last reason',
    ],

    'actions' => ['active' => 'Activate', 'paused' => 'Pause', 'archived' => 'Archive', 'draft' => 'Back to draft'],
    'confirm' => [
        'title' => 'Change campaign status',
        'message' => [
            'active' => 'The campaign starts earning for qualifying activity inside its window.',
            'paused' => 'New referrals / progress stop being counted. Rewards already earned stay. A reason is required.',
            'archived' => 'The campaign is closed for good. A reason is required.',
            'draft' => 'The campaign goes back to draft.',
        ],
    ],
    'adjust' => ['title' => 'Adjust budget', 'hint' => 'Set a new total budget. It cannot be lower than what was already spent. A reason is required and is audited.'],

    'referral' => [
        'menu' => 'Referral campaigns', 'title' => 'Referral campaigns', 'subtitle' => 'Reward customers and providers who bring new people. Rewards are paid through the ledger when the new person completes the milestone.',
        'create' => 'New referral campaign', 'edit' => 'Edit referral campaign', 'show_subtitle' => 'Referral campaign',
        'form_notice' => 'Customer credit expires after the validity period; provider rewards are paid to the provider wallet and do not expire. The campaign only earns while it is active and inside its window, within the budget and the per-referrer cap.',
        'no_expiry' => 'No expiry (provider wallet)',
        'sections' => ['rewards' => 'Rewards and milestone', 'rewards_hint' => 'What each side receives and what the new person must do first.', 'limits' => 'Limits and eligibility', 'limits_hint' => 'Budget, cap per referrer and which jobs count.'],
        'fields' => [
            'rewards' => 'Referrer / new person', 'referrer_reward' => 'Referrer reward', 'referee_reward' => 'New person reward', 'milestone' => 'Completed jobs needed', 'valid_days' => 'Credit validity (days)',
            'cap' => 'Rewards per referrer', 'referrer' => 'Referrer', 'referee' => 'New person', 'jobs' => 'Jobs', 'qualified_at' => 'Milestone reached', 'decided_at' => 'Decided', 'decision_reason' => 'Decision note',
        ],
        'hints' => [
            'milestone' => 'The new person must complete this many qualifying jobs.', 'valid_days' => 'Customer credit only.', 'cap' => 'Maximum number of people one referrer can be rewarded for.',
            'budget' => 'Rewards stop when the budget cannot cover both rewards.', 'min_order' => 'Jobs below this fare do not count. Leave empty for any.',
        ],
        'totals' => ['relationships' => 'Referrals', 'rewarded' => 'Rewarded', 'under_review' => 'Under review'],
        'review_notice' => 'A weak signal holds the reward for a person to look at. Nothing is restricted automatically. Approve to release the reward when the milestone is reached, or reject with a reason.',
        'flags_title' => 'Why it was flagged', 'flags_hint' => 'Counts and checks only. No contact data is shown.', 'no_flags' => 'No flags.',
        'actions' => ['approve' => 'Approve', 'reject' => 'Reject'],
        'confirm' => ['approve_title' => 'Approve this referral', 'approve_message' => 'The reward is paid when the milestone is reached. You can add a note.', 'reject_title' => 'Reject this referral', 'reject_message' => 'No reward will be paid. A reason is required.'],
        'approved' => 'Referral approved.', 'rejected' => 'Referral rejected.',
    ],

    'relationships' => ['menu' => 'Referrals', 'title' => 'Referrals', 'subtitle' => 'Who referred whom, progress to the milestone and review flags.', 'show_title' => 'Referral'],
    'rewards' => [
        'menu' => 'Referral rewards', 'title' => 'Referral rewards', 'subtitle' => 'Every reward is one ledger entry. Customer credit shows when it expires and how much was taken back.', 'none' => 'No rewards paid yet.',
        'fields' => ['amount' => 'Amount', 'beneficiary' => 'Paid to', 'expired' => 'Taken back', 'expires' => 'Expires', 'granted' => 'Granted', 'recipient' => 'Recipient'],
    ],

    'loyalty' => [
        'menu' => 'Loyalty levels', 'title' => 'Loyalty levels', 'subtitle' => 'Customers reach a level by completed jobs. A level can grant a one-time credit with an expiry.',
        'create' => 'New loyalty level', 'edit' => 'Edit loyalty level',
        'fields' => ['min_jobs' => 'Completed jobs', 'reward' => 'One-time credit', 'valid_days' => 'Credit validity (days)', 'sort' => 'Sort'],
        'hints' => ['min_jobs' => 'Thresholds are unique.', 'reward' => 'Zero for a level with no credit.'],
        'actions' => ['activate' => 'Activate', 'deactivate' => 'Deactivate'],
        'confirm' => ['title' => 'Change level', 'message' => 'Awards already given are kept. A reason is required to deactivate.'],
    ],

    'incentive' => [
        'menu' => 'Provider incentives', 'title' => 'Provider incentive campaigns', 'subtitle' => 'Quests that pay a bonus to providers who complete a number of qualifying jobs.',
        'create' => 'New incentive campaign', 'edit' => 'Edit incentive campaign', 'show_subtitle' => 'Provider incentive campaign',
        'form_notice' => 'The bonus is posted to the provider wallet through the ledger when the quest target is reached, within the budget. Suspicious progress is held for a person to review.',
        'quest_line' => ':jobs jobs = :bonus',
        'sections' => ['quest' => 'Quest', 'quest_hint' => 'Target, bonus and budget.', 'eligibility' => 'Eligibility', 'eligibility_hint' => 'Only approved providers; narrow it by rating, order value, service and city.', 'fraud' => 'Fraud rules', 'fraud_hint' => 'Limits that keep one customer from filling a quest.'],
        'fields' => [
            'quest' => 'Quest', 'target' => 'Jobs to complete', 'bonus' => 'Bonus', 'max_awards' => 'Times a provider can earn it', 'min_rating' => 'Minimum rating', 'max_per_customer' => 'Counted jobs per customer',
            'provider' => 'Provider', 'progress' => 'Progress', 'jobs' => 'Jobs counted', 'awards' => 'Bonuses earned',
        ],
        'hints' => ['max_awards' => 'Progress continues after each bonus.', 'budget' => 'No bonus is paid when the remaining budget cannot cover it.', 'max_per_customer' => 'Further jobs for the same customer do not count.'],
        'totals' => ['providers' => 'Providers', 'awards' => 'Bonuses paid', 'held' => 'Held for review'],
        'progress_title' => 'Provider progress', 'progress_hint' => 'Held rows wait for a person: release pays the bonus for jobs already counted; reject pays nothing.',
        'actions' => ['release' => 'Release', 'reject' => 'Reject'],
        'confirm' => ['release_title' => 'Release this progress', 'release_message' => 'The bonus is paid for the jobs already counted.', 'reject_title' => 'Reject this progress', 'reject_message' => 'No bonus will be paid. A reason is required.'],
        'released' => 'Progress released.', 'rejected' => 'Progress rejected.',
    ],

    'enums' => [
        'audience' => ['customer' => 'Customers', 'provider' => 'Providers'],
        'campaign_status' => ['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'],
        'relationship_status' => ['pending' => 'In progress', 'under_review' => 'Under review', 'rewarded' => 'Rewarded', 'rejected' => 'Rejected'],
        'recipient' => ['referrer' => 'Referrer', 'referee' => 'New person'],
        'credit_status' => ['granted' => 'Active', 'expired' => 'Expired', 'reversed' => 'Reversed'],
        'progress_status' => ['in_progress' => 'In progress', 'held' => 'Held for review', 'exhausted' => 'Waiting for budget', 'rejected' => 'Rejected'],
        'fraud_flag' => [
            'same_device' => 'Same device', 'same_phone_pattern' => 'Similar phone numbers', 'same_payment_card' => 'Same saved card', 'velocity' => 'Many referrals in a short time',
            'linked_account' => 'Several accounts on one device', 'customer_repeat' => 'Same customer repeatedly', 'risk_signal_open' => 'Open risk signal',
        ],
    ],

    // Short texts of the app responses (referral share text, status labels).
    'app' => [
        'share_text' => 'Join me on the app and get a reward. Use my code :code or open :link',
        'credits_note' => 'Promotional credit is used first when you pay from your wallet. Unused credit expires on the date shown.',
        'applied' => 'Referral code applied.',
        'level_none' => 'No level yet',
    ],
];
