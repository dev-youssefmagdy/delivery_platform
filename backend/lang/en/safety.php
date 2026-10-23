<?php

/*
| Safety centre (P5.1): enum labels, SMS texts to trusted contacts, API messages and the admin Safety Center.
*/
return [
    'enums' => [
        'category' => [
            'emergency' => 'Emergency', 'accident' => 'Accident', 'medical' => 'Medical problem', 'threat' => 'Threat or violence',
            'harassment' => 'Harassment', 'unsafe_driving' => 'Unsafe driving', 'theft' => 'Theft or lost belongings',
            'identity_mismatch' => 'Driver or vehicle does not match', 'other' => 'Other safety concern',
        ],
        'severity' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'],
        'priority' => ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'],
        'status' => ['new' => 'New', 'acknowledged' => 'Acknowledged', 'investigating' => 'Investigating', 'escalated' => 'Escalated', 'resolved' => 'Resolved'],
        'trigger' => ['sos' => 'SOS button', 'report' => 'Incident report'],
        'event' => [
            'created' => 'Case raised', 'repeat_sos' => 'SOS pressed again', 'user_update' => 'Update from the person', 'attachment_added' => 'File added',
            'trusted_contacts_notified' => 'Trusted contacts notified', 'acknowledged' => 'Acknowledged', 'assigned' => 'Assigned', 'unassigned' => 'Unassigned',
            'note' => 'Staff note', 'public_message' => 'Message to the person', 'severity_changed' => 'Severity changed', 'priority_changed' => 'Priority changed',
            'escalated' => 'Escalated', 'resolved' => 'Resolved', 'reopened' => 'Reopened', 'legal_hold_set' => 'Legal hold set',
            'legal_hold_released' => 'Legal hold released', 'sla_breached' => 'Response time exceeded', 'exported' => 'Evidence exported',
        ],
        'escalation' => ['safety_lead' => 'Safety lead', 'operations' => 'Operations', 'legal' => 'Legal', 'authorities' => 'Authorities contacted by staff'],
        'outcome' => [
            'resolved_safe' => 'Person confirmed safe', 'action_taken' => 'Action taken', 'referred_to_authorities' => 'Referred to authorities',
            'unable_to_reach' => 'Could not reach the person', 'false_alarm' => 'False alarm',
        ],
        'contact_action' => ['sos_alert' => 'SOS alert', 'trip_share' => 'Trip link'],
        'contact_action_status' => ['sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Not sent'],
        'relationship' => ['family' => 'Family', 'partner' => 'Partner', 'friend' => 'Friend', 'colleague' => 'Colleague', 'other' => 'Other'],
    ],

    'emergency' => ['police' => 'Police', 'ambulance' => 'Ambulance', 'fire' => 'Fire brigade', 'tourist_police' => 'Tourist police'],

    'sms' => [
        'someone' => 'Someone you know',
        'sos_alert' => ':name pressed the safety button during a trip at :time and listed you as a trusted contact. Please try to reach them.',
        'position' => 'Last position: :link',
        'trip_share' => ':name is sharing a trip with you: :url (link works for about :minutes minutes).',
    ],

    'actions' => [
        'incident_recorded' => 'Your alert was recorded as case :number.',
        'location_captured' => 'Your location was captured and sent with the alert.',
        'location_missing' => 'Your location was not available, so none was sent.',
        'team_notified' => 'The safety team was notified and will see your case first.',
        'team_queued' => 'Your case is at the top of the safety team queue. No officer was online to be notified right now.',
        'contacts_none' => 'You have no trusted contacts to notify.',
        'contacts_skipped' => 'Your trusted contacts did not receive a message because of their consent settings.',
        'contacts_sent' => '{1} Your trusted contact was sent a text message.|[2,*] :count of your trusted contacts were sent a text message.',
        'contacts_partial' => 'Text messages: :sent sent, :failed could not be sent.',
        'emergency_not_contacted' => 'The app did NOT call emergency services and no one was dispatched. If you are in danger, call the local emergency number yourself.',
    ],

    'api' => [
        'sos_received' => 'Your safety alert was received.',
        'report_received' => 'Your report was received.',
        'update_received' => 'Your update was added to the case.',
        'attachment_received' => 'The file was added to the case.',
        'contact_saved' => 'Trusted contact saved.',
        'contact_removed' => 'Trusted contact removed.',
        'trip_shared' => 'Trip link sent to :sent trusted contact(s).',
        'trip_share_stopped' => 'Trip sharing stopped.',
    ],

    'admin' => [
        'menu' => 'Safety Center',
        'title' => 'Safety Center',
        'subtitle' => ':urgent urgent new, :active open cases',
        'legal_hold' => 'Legal hold',
        'hold_notice' => 'This case is under legal hold: its evidence is kept through retention clean-up and account deletion.',
        'sla_met' => 'Acknowledged',
        'sla_breached' => 'Response time exceeded',
        'sla_overdue' => 'Overdue by :time',
        'sla_in' => 'Due in :time',
        'unassigned' => 'Unassigned',
        'timeline_hint' => 'The overview shows what happened and when. Message and note texts are evidence and appear only in the Evidence tab.',
        'evidence_forbidden' => 'Evidence needs the "view incident evidence" permission.',
        'evidence_notice' => 'Every time this tab is opened, or a file is opened or exported, the access is written to the audit log with your name.',
        'quick' => ['all' => 'All cases', 'urgent' => 'Urgent', 'mine' => 'My cases', 'unassigned' => 'Unassigned'],
        'tabs' => ['timeline' => 'Timeline', 'evidence' => 'Evidence'],
        'fields' => [
            'number' => 'Number', 'trigger' => 'Raised by', 'category' => 'Category', 'severity' => 'Severity', 'priority' => 'Priority', 'status' => 'Status',
            'reporter' => 'Person', 'booking' => 'Booking', 'sla' => 'Acknowledge by', 'assignee' => 'Officer', 'occurred_at' => 'Raised at',
            'acknowledged_at' => 'Acknowledged at', 'resolved_at' => 'Resolved at', 'outcome' => 'Outcome', 'sla_breached' => 'Response time exceeded',
            'note' => 'Staff note', 'message' => 'Message to the person', 'escalation_level' => 'Escalate to', 'public_message' => 'Message shown to the person (optional)',
        ],
        'linked' => ['title' => 'Linked records', 'reporter' => 'Person'],
        'actions' => [
            'title' => 'Actions', 'acknowledge' => 'Acknowledge', 'reopen' => 'Reopen', 'assign' => 'Assign', 'triage' => 'Update severity and priority',
            'escalate' => 'Escalate', 'resolve' => 'Resolve', 'set_hold' => 'Set legal hold', 'release_hold' => 'Release legal hold', 'export_bundle' => 'Export evidence bundle',
        ],
        'confirm' => [
            'acknowledge_title' => 'Acknowledge this case?', 'acknowledge_message' => 'The person is told that a safety officer received the case, and it is assigned to you if nobody has it.',
            'reopen_title' => 'Reopen this case?', 'reopen_message' => 'The case returns to the active queue. A reason is required.',
            'escalate_title' => 'Escalate this case?', 'escalate_message' => 'Escalation is recorded with your reason. It does not contact anyone outside the platform.',
            'resolve_title' => 'Resolve this case?', 'resolve_message' => 'The person is notified. Resolving needs an outcome and a written reason.',
        ],
        'message' => ['title' => 'Message the person', 'subtitle' => 'The person reads this in the app and is notified. Counts as acknowledgement of a new case.', 'send' => 'Send'],
        'note' => ['title' => 'Staff note', 'subtitle' => 'Visible to safety staff with evidence access only.', 'save' => 'Save note'],
        'resolve' => ['reason' => 'Reason (internal, audited)', 'reason_hint' => 'Why the case can be closed and what was done.', 'public_hint' => 'Shown to the person in the app. Do not include internal details.'],
        'hold_hint' => 'Set or release the hold. A reason is required and audited.',
        'done' => [
            'acknowledged' => 'Case acknowledged.', 'assigned' => 'Assignment saved.', 'note' => 'Note saved.', 'message' => 'Message sent.', 'triage' => 'Severity and priority updated.',
            'escalated' => 'Case escalated.', 'resolved' => 'Case resolved.', 'reopened' => 'Case reopened.', 'hold_set' => 'Legal hold set.', 'hold_released' => 'Legal hold released.',
        ],
        'evidence' => [
            'narrative' => 'What the person wrote', 'no_narrative' => 'No text was written.', 'callback' => 'Callback phone', 'location' => 'Location snapshot',
            'fix' => ':source · captured :time · accuracy :accuracy', 'no_location' => 'No location was available.', 'counterpart' => 'Other side last known position',
            'source' => ['device' => 'Phone GPS', 'last_known' => 'Last known position'], 'files' => 'Files', 'no_files' => 'No files.', 'open_file' => 'Open',
            'files_hint' => 'Links expire after :minutes minutes; every open is audited.', 'contacts' => 'Trusted contacts: what the platform did', 'no_contacts' => 'No trusted contact was involved.',
            'with_location' => 'location included', 'thread' => 'Messages and notes', 'internal' => 'staff only', 'no_thread' => 'No messages or notes.',
        ],
        'booking_tab' => [
            'title' => 'Safety cases', 'none' => 'No safety case was raised on this booking.',
            'hint' => 'Proofs, custody and delivery attempts are in the "Delivery and load" tab.',
        ],
    ],
];
