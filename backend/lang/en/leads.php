<?php

/*
| Onboarding leads (P5.5): enum labels and the admin Leads module. The public form text is in site.php.
*/
return [
    'menu' => 'Provider leads',
    'title' => 'Provider leads',
    'subtitle' => ':waiting new lead(s) are waiting for a first contact.',
    'show_title' => 'Lead',
    'updated' => 'Lead updated.',
    'moved' => 'Lead status changed.',
    'public_notice' => 'Leads come from the public "become a provider" form. They hold contact data and interest only; documents are collected later inside the provider app.',

    'enums' => [
        'status' => ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'rejected' => 'Rejected', 'spam' => 'Spam'],
        'type' => ['provider_onboarding' => 'Provider onboarding'],
    ],

    'actions' => [
        'contacted' => 'Mark contacted',
        'qualified' => 'Mark qualified',
        'converted' => 'Mark converted',
        'rejected' => 'Reject',
        'spam' => 'Mark as spam',
        'new' => 'Reopen',
    ],
    'confirm' => [
        'title' => 'Change lead status',
        'message' => 'The change is recorded in the audit log.',
        'reason_hint' => 'Rejecting, marking as spam and reopening need a reason.',
    ],

    'fields' => [
        'reference' => 'Reference',
        'contact' => 'Contact',
        'name' => 'Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'city' => 'City',
        'service' => 'Service interest',
        'vehicle' => 'Vehicle / experience',
        'message' => 'Message',
        'created' => 'Received',
        'status' => 'Status',
        'status_reason' => 'Last reason',
        'language' => 'Language',
        'consent' => 'Privacy notice accepted',
        'owner' => 'Owner',
        'notes' => 'Internal notes',
        'source' => 'Source',
    ],
    'notes_hint' => 'Visible to staff only.',
];
