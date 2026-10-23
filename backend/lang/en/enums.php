<?php

/*
| Labels for every App\Enums case: enums.{snake_enum}.{value}.
*/
return [
    'badge_color' => [
        'neutral' => 'Neutral',
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
        'danger' => 'Danger',
        'primary' => 'Primary',
    ],

    'locale' => [
        'ar' => 'العربية',
        'en' => 'English',
    ],

    'actor_type' => [
        'customer' => 'Customer',
        'provider' => 'Provider',
        'admin' => 'Admin',
        'system' => 'System',
    ],

    'platform' => [
        'android' => 'Android',
        'ios' => 'iOS',
        'web' => 'Web',
    ],

    'audit_action' => [
        'assigned' => 'Assigned',
        'escalated' => 'Escalated',
        'resolved' => 'Resolved',
        'reopened' => 'Reopened',
        'linked' => 'Linked',
        'requested' => 'Requested',
        'reprocessed' => 'Reprocessed',
        'cash_collected' => 'Cash collected',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'status_changed' => 'Status changed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'reactivated' => 'Reactivated',
        'published' => 'Published',
        'exported' => 'Exported',
        'viewed' => 'Viewed',
        'logged_in' => 'Logged in',
        'logged_out' => 'Logged out',
        'login_failed' => 'Login failed',
        'overridden' => 'Overridden',
        'mfa_enabled' => 'Two-step verification enabled',
        'mfa_disabled' => 'Two-step verification disabled',
        'mfa_reset' => 'Two-step verification reset',
        'password_changed' => 'Password changed',
        'session_revoked' => 'Session revoked',
        'restricted' => 'Restricted',
        'message_sent' => 'Message sent',
        'phone_revealed' => 'Phone number revealed',
        'anonymised' => 'Anonymised',
        'reversed' => 'Reversed',
        'reconciled' => 'Reconciled',
        'downloaded' => 'Downloaded',
        'more_info_requested' => 'More information requested',
    ],

    'health_status' => [
        'ok' => 'Operational',
        'degraded' => 'Degraded',
        'failing' => 'Failing',
        'not_configured' => 'Not configured',
    ],

    'user_status' => [
        'active' => 'Active',
        'restricted' => 'Restricted',
        'suspended' => 'Suspended',
        'verification_required' => 'Verification required',
        'deletion_pending' => 'Deletion pending',
        'deleted' => 'Deleted',
    ],

    'client_app' => ['customer' => 'Customer app', 'provider' => 'Driver app'],

    'otp_purpose' => ['login' => 'Sign in'],

    'otp_delivery_status' => ['sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Test number (not sent)'],

    'otp_challenge_state' => [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'expired' => 'Expired',
        'locked' => 'Locked',
        'superseded' => 'Superseded',
    ],

    'deletion_request_status' => ['pending' => 'Pending', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected', 'completed' => 'Completed'],

    'admin_permission' => [
        'label' => ':group: :action',
    ],

    'admin_permission_group' => [
        'dashboard' => 'Dashboard',
        'admins' => 'Admin users',
        'roles' => 'Roles',
        'audit_logs' => 'Audit logs',
        'login_activity' => 'Login activity',
        'otp_challenges' => 'OTP challenges',
        'settings' => 'Settings',
        'markets' => 'Markets',
        'policies' => 'Policies',
        'customers' => 'Customers',
        'drivers' => 'Drivers',
        'documents' => 'Documents',
        'vehicles' => 'Vehicles',
        'services' => 'Services',
        'vehicle_categories' => 'Vehicle categories',
        'service_areas' => 'Service areas',
        'fare_policies' => 'Fare policies',
        'commission_rules' => 'Commission rules',
        'cancellation_policies' => 'Cancellation policies',
        'bookings' => 'Bookings',
        'offers' => 'Offers',
        'coupons' => 'Coupons',
        'campaigns' => 'Campaigns',
        'finance' => 'Finance',
        'payments' => 'Payments',
        'refunds' => 'Refunds',
        'payouts' => 'Payouts',
        'wallets' => 'Wallets',
        'ledger' => 'Ledger',
        'incidents' => 'Incidents',
        'support' => 'Support',
        'macros' => 'Macros',
        'reviews' => 'Reviews',
        'risk' => 'Risk',
        'content' => 'Content',
        'translations' => 'Translations',
        'leads' => 'Leads',
        'notifications' => 'Notifications',
        'push_campaigns' => 'Push campaigns',
        'reports' => 'Reports',
        'system' => 'System',
        'demo_items' => 'Demo items',
        // @admin-module:permission-groups
    ],

    'admin_permission_action' => [
        'view' => 'View',
        'manage' => 'Manage',
        'publish' => 'Publish',
        'view_pii' => 'View personal data',
        'update' => 'Update',
        'suspend' => 'Suspend',
        'export' => 'Export',
        'process_deletion' => 'Process deletion',
        'verify' => 'Verify',
        'reject' => 'Reject',
        'manage_types' => 'Manage types',
        'approve' => 'Approve',
        'cancel' => 'Cancel',
        'assign_override' => 'Override assignment',
        'pin_override' => 'Override PIN',
        'moderate' => 'Moderate',
        'reconcile' => 'Reconcile',
        'request' => 'Request',
        'review' => 'Review',
        'adjust' => 'Adjust',
        'approve_adjustment' => 'Approve adjustments',
        'view_evidence' => 'View evidence',
        'export_restricted' => 'Export restricted data',
        'view_all' => 'View all',
        'health' => 'Health',
        'feature_flags' => 'Feature flags',
        'app_versions' => 'App versions',
        'queues' => 'Queues',
    ],

    'admin_export_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Ready',
        'failed' => 'Failed',
    ],

    'demo_item_status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'demo_item_type' => [
        'standard' => 'Standard',
        'premium' => 'Premium',
        'bundle' => 'Bundle',
    ],

    'demo_item_tag' => [
        'new' => 'New',
        'popular' => 'Popular',
        'seasonal' => 'Seasonal',
        'limited' => 'Limited',
    ],

    'admin_status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],

    'admin_login_outcome' => [
        'success' => 'Signed in',
        'invalid_credentials' => 'Invalid credentials',
        'account_suspended' => 'Suspended account',
        'mfa_failed' => 'Wrong verification code',
        'throttled' => 'Blocked (too many attempts)',
        'logout' => 'Signed out',
    ],

    'admin_role' => [
        'super_admin' => 'Super Admin',
        'operations_admin' => 'Operations Admin',
        'dispatch_agent' => 'Dispatch / Live Operations Agent',
        'driver_verification_officer' => 'Driver Verification Officer',
        'customer_support_agent' => 'Customer Support Agent',
        'safety_incident_officer' => 'Safety & Incident Officer',
        'finance_manager' => 'Finance Manager',
        'finance_payout_officer' => 'Finance Payout Officer',
        'marketing_coupon_manager' => 'Marketing / Coupon Manager',
        'content_cms_editor' => 'Content / CMS Editor',
        'read_only_auditor' => 'Analyst / Read-only Auditor',
        'system_integration_admin' => 'System / Integration Administrator',
    ],

    'digits_preference' => [
        'auto' => 'Follow the language',
        'western' => 'Western digits (0-9)',
        'arabic_indic' => 'Arabic-Indic digits (٠-٩)',
    ],

    'setting_group' => [
        'localization' => 'Language & market',
        'support' => 'Support contacts',
        'otp' => 'Verification codes',
        'booking' => 'Bookings & offers',
        'dispatch' => 'Dispatch',
        'providers' => 'Providers',
        'safety' => 'Safety & privacy',
        'apps' => 'Mobile apps',
        'security' => 'Admin security',
        'finance' => 'Finance',
        'rate_limits' => 'Rate limits',
    ],

    'content_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'policy_type' => [
        'terms' => 'Terms and conditions',
        'privacy' => 'Privacy policy',
        'driver_terms' => 'Driver terms',
        'coupon_rules' => 'Coupon rules',
        'restricted_goods' => 'Restricted goods',
        'cancellation' => 'Cancellation policy',
    ],

    'zone_status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],

    'saved_place_type' => [
        'home' => 'Home',
        'work' => 'Work',
        'other' => 'Other',
    ],

    'location_precision' => [
        'exact' => 'Exact address',
        'street' => 'Street',
        'approximate' => 'Approximate',
    ],

    'availability_reason' => [
        'available' => 'Available',
        'not_in_zone' => 'Not offered in this zone',
        'zone_closed' => 'The zone is closed at this time',
        'category_closed' => 'The service is closed at this time',
        'override_closed' => 'Closed at this time in this zone',
    ],

    'maps_provider_status' => [
        'ok' => 'Operational',
        'not_configured' => 'Not configured',
        'unavailable' => 'Unavailable',
    ],

    'service_family_kind' => [
        'passenger_city' => 'City passenger rides',
        'intercity' => 'Intercity rides',
        'courier' => 'Courier and parcels',
        'moving' => 'Furniture moving',
        'freight' => 'Freight and cargo',
        'hourly' => 'Hourly hire',
        'other' => 'Other',
    ],

    'booking_type' => [
        'on_demand' => 'On demand',
        'scheduled' => 'Scheduled',
        'intercity' => 'Intercity',
        'parcel' => 'Parcel',
        'freight' => 'Freight',
        'hourly' => 'Hourly',
    ],

    'contact_role' => [
        'sender' => 'Sender',
        'recipient' => 'Recipient',
        'pickup_contact' => 'Pickup contact',
        'dropoff_contact' => 'Drop-off contact',
    ],

    'custody_status' => [
        'picked_up' => 'Picked up',
        'in_transit' => 'In transit',
        'arrived_dropoff' => 'Arrived at drop-off',
        'attempt_failed' => 'Delivery attempt failed',
        'returning' => 'Returning to sender',
        'delivered' => 'Delivered',
        'returned' => 'Returned to sender',
    ],

    'delivery_attempt_outcome' => [
        'recipient_unavailable' => 'Recipient not available',
        'recipient_refused' => 'Recipient refused the parcel',
        'wrong_address' => 'Wrong or unreachable address',
        'otp_failed' => 'Recipient could not give the code',
        'restricted_item' => 'Restricted item found',
        'other' => 'Other',
    ],

    'delivery_attempt_resolution' => [
        'retry' => 'Try again',
        'return_to_sender' => 'Return to sender',
        'operations' => 'Hand over to support',
    ],

    'proof_stage' => [
        'pickup' => 'Pickup',
        'delivery' => 'Delivery',
        'return' => 'Return',
    ],

    'recipient_otp_delivery' => [
        'pending' => 'Sending',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'skipped' => 'Test number (not sent)',
    ],

    'cargo_type' => [
        'general' => 'General goods',
        'building' => 'Building materials',
        'food' => 'Food',
        'machinery' => 'Machinery',
        'documents' => 'Documents',
        'clothes' => 'Clothes',
        'electronics' => 'Electronics',
        'other' => 'Other',
    ],

    'parcel_size' => [
        'small' => 'Small (envelope)',
        'medium' => 'Medium (shoe box)',
        'large' => 'Large (bag)',
    ],

    'proof_type' => [
        'photo' => 'Photo',
        'signature' => 'Signature',
        'recipient_otp' => 'Recipient code',
        'pickup_pin' => 'Pickup PIN',
        'checklist' => 'Checklist',
        'weight' => 'Weight',
    ],

    'payment_method_type' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'mobile_wallet' => 'Mobile wallet',
        'platform_wallet' => 'Platform wallet',
    ],

    'visibility_operator' => [
        'eq' => 'equals',
        'neq' => 'does not equal',
        'in' => 'is one of',
        'not_in' => 'is not one of',
        'gt' => 'is greater than',
        'gte' => 'is at least',
        'lt' => 'is less than',
        'lte' => 'is at most',
        'filled' => 'is answered',
        'empty' => 'is empty',
    ],

    'form_field_type' => [
        'text' => 'Short text',
        'textarea' => 'Long text',
        'integer' => 'Whole number',
        'decimal' => 'Decimal number',
        'boolean' => 'Yes / no',
        'select' => 'Single choice',
        'multiselect' => 'Multiple choice',
        'date' => 'Date',
        'time' => 'Time',
        'datetime' => 'Date and time',
        'contact' => 'Contact (name and phone)',
        'photo' => 'Photos',
        'item_checklist' => 'Item checklist',
        'dimensions' => 'Dimensions (L x W x H)',
        'weight' => 'Weight (kg)',
    ],

    'weekday' => [
        '1' => 'Monday',
        '2' => 'Tuesday',
        '3' => 'Wednesday',
        '4' => 'Thursday',
        '5' => 'Friday',
        '6' => 'Saturday',
        '7' => 'Sunday',
    ],

    'provider_type' => [
        'individual_driver' => 'Individual driver',
        'courier' => 'Courier',
        'moving_crew' => 'Moving crew / driver',
        'commercial_transport' => 'Commercial transport provider',
        'company_fleet' => 'Company / fleet',
    ],

    'provider_status' => [
        'draft' => 'Draft',
        'phone_verified' => 'Phone verified',
        'profile_submitted' => 'Profile submitted',
        'documents_pending' => 'Documents pending',
        'under_review' => 'Under review',
        'more_information_required' => 'More information required',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'temporarily_suspended' => 'Temporarily suspended',
        'permanently_suspended' => 'Permanently suspended',
        'deletion_pending' => 'Deletion pending',
    ],

    'document_status' => [
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],

    'vehicle_status' => [
        'draft' => 'Draft',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'inactive' => 'Inactive',
    ],

    'eligibility_reason' => [
        'not_approved' => 'The application is not approved yet.',
        'provider_suspended' => 'The provider account is suspended.',
        'account_blocked' => 'The account is blocked or restricted.',
        'document_missing' => 'A required document is missing.',
        'document_not_approved' => 'A required document is not approved yet.',
        'document_expired' => 'A required document has expired.',
        'no_approved_vehicle' => 'There is no approved vehicle.',
        'vehicle_not_approved' => 'The vehicle is not approved.',
        'service_not_approved' => 'No approved service for this category.',
        'vehicle_category_mismatch' => 'The vehicle category does not match this service.',
        'location_stale' => 'Your location is missing or out of date.',
        'active_job_exists' => 'You already have an active job.',
        'cash_debt_limit_exceeded' => 'Your unsettled commission is over the allowed limit.',
        'cancellation_cooldown' => 'You are paused for a while after repeated cancellations or no-shows.',
    ],

    'tracking_state' => [
        'off' => 'Not tracking',
        'idle' => 'Waiting for requests',
        'to_pickup' => 'Heading to pickup',
        'active' => 'Active job',
    ],

    'location_source' => [
        'gps' => 'GPS',
        'fused' => 'Fused',
        'network' => 'Network',
    ],

    'location_anomaly' => [
        'impossible_speed' => 'Impossible speed between samples',
        'future_timestamp' => 'Timestamp in the future',
    ],

    'offline_reason' => [
        'provider_choice' => 'The provider went offline',
        'stale_location' => 'The location became stale',
        'no_longer_eligible' => 'The provider is no longer eligible',
        'account_changed' => 'The account status changed',
    ],

    'dispatch_response' => [
        'pending' => 'Waiting',
        'offered' => 'Offered',
        'declined' => 'Declined',
        'expired' => 'Expired',
    ],

    'document_subject' => [
        'provider' => 'Provider',
        'vehicle' => 'Vehicle',
    ],

    'vehicle_capability' => [
        'air_conditioning' => 'Air conditioning',
        'child_seat' => 'Child seat',
        'wheelchair_accessible' => 'Wheelchair accessible',
        'pets_allowed' => 'Pets allowed',
        'large_luggage' => 'Large luggage',
        'covered_cargo' => 'Covered cargo area',
        'tail_lift' => 'Tail lift',
        'refrigerated' => 'Refrigerated',
        'moving_equipment' => 'Moving equipment',
    ],

    'vehicle_photo_kind' => [
        'front' => 'Front',
        'back' => 'Back',
        'side' => 'Side',
        'interior' => 'Interior',
        'other' => 'Other',
    ],

    'service_approval_status' => [
        'requested' => 'Requested',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'file_scan_status' => [
        'clean' => 'Scanned, clean',
        'infected' => 'Infected',
        'skipped' => 'Not scanned (scanner disabled)',
        'not_scanned' => 'Not scanned (no scanner configured)',
    ],

    'onboarding_task' => [
        'complete_profile' => 'Complete your profile',
        'select_services' => 'Choose the services you offer',
        'add_vehicle' => 'Add a vehicle',
        'add_vehicle_photos' => 'Add vehicle photos',
        'submit_vehicle' => 'Submit the vehicle for review',
        'upload_document' => 'Upload a required document',
        'replace_rejected_document' => 'Replace a rejected document',
        'renew_expired_document' => 'Renew an expired document',
        'fix_vehicle' => 'Fix the vehicle and submit it again',
        'accept_policies' => 'Accept the driver terms',
        'submit_application' => 'Submit your application',
    ],

    'verification_decision' => [
        'approve' => 'Approve',
        'request_more_information' => 'Request more information',
        'reject' => 'Reject',
    ],

    'booking_status' => [
        'draft' => 'Draft',
        'searching_offers' => 'Finding offers',
        'offers_received' => 'Offers received',
        'provider_selected' => 'Provider selected',
        'provider_heading_to_pickup' => 'Provider on the way',
        'provider_arrived' => 'Provider arrived',
        'in_progress' => 'In progress',
        'delivery_attempt_failed' => 'Delivery attempt failed',
        'completed_pending_payment' => 'Completed, payment pending',
        'completed' => 'Completed',
        'disputed' => 'Disputed',
        'cancelled_by_customer' => 'Cancelled by customer',
        'cancelled_by_provider' => 'Cancelled by provider',
        'cancelled_by_operations' => 'Cancelled by operations',
        'expired_no_provider' => 'Expired, no offers',
    ],

    'booking_action' => [
        'view_proofs' => 'View proofs',
        'resolve_delivery_attempt' => 'Decide after failed delivery',
        'chat' => 'Chat',
        'appeal' => 'Appeal fee',
        'pay' => 'Pay online',
        'tip' => 'Leave a tip',
        'update_request' => 'Edit the request',
        'select_offer' => 'Choose a driver',
        'cancel' => 'Cancel the booking',
        'share' => 'Share the trip',
        'rate' => 'Rate the trip',
        'request_adjustment' => 'Change the fare',
        'view_receipt' => 'View the receipt',
        'report' => 'Report a problem',
    ],

    'offer_status' => [
        'submitted' => 'Offer at your fare',
        'counter_offered' => 'Counter-offer',
        'accepted_by_customer' => 'Chosen',
        'rejected_by_customer' => 'Declined',
        'withdrawn_by_provider' => 'Withdrawn',
        'expired' => 'Expired',
        'invalidated_request_changed' => 'Request changed',
        'invalidated_provider_unavailable' => 'Driver unavailable',
        'lost_booking_assigned' => 'Another driver was chosen',
    ],

    'offer_kind' => [
        'accept_customer_fare' => 'Accepts your fare',
        'counter_offer' => 'Counter-offer',
    ],

    'fare_hint' => [
        'below_reference_estimate' => 'Below the usual price guide',
        'above_reference_estimate' => 'Above the usual price guide',
        'below_recent_offers' => 'Lower than the other offers',
        'above_recent_offers' => 'Higher than the other offers',
    ],

    'offer_invalidation_reason' => [
        'request_changed' => 'The request was changed',
        'search_expired' => 'The search window ended',
        'booking_cancelled' => 'The booking was cancelled',
        'provider_unavailable' => 'The driver became unavailable',
    ],

    'payment_status' => [
        'not_required' => 'No payment required',
        'pending' => 'Payment pending',
        'requires_action' => 'Action required',
        'authorized' => 'Authorized',
        'paid' => 'Paid',
        'failed' => 'Payment failed',
        'partially_refunded' => 'Partially refunded',
        'refunded' => 'Refunded',
        'cash_due' => 'Cash due',
        'cash_collected' => 'Cash collected',
        'reconciliation_required' => 'Needs reconciliation',
    ],

    'payment_gateway' => [
        'sandbox' => 'Sandbox (test)',
        'paymob' => 'Paymob',
    ],

    'payment_kind' => [
        'trip' => 'Trip',
        'tip' => 'Tip',
        'debt' => 'Wallet debt',
    ],

    'payment_event_type' => [
        'intent_created' => 'Payment started',
        'status_changed' => 'Status changed',
        'webhook_applied' => 'Gateway webhook applied',
        'webhook_ignored' => 'Gateway webhook ignored',
        'query_applied' => 'Gateway query applied',
        'cash_collected' => 'Cash collected',
        'settled' => 'Posted to the ledger',
        'timed_out' => 'No confirmation in time',
        'amount_mismatch' => 'Amount mismatch',
        'refund_requested' => 'Refund requested',
        'refund_approved' => 'Refund approved',
        'refund_rejected' => 'Refund rejected',
        'refund_completed' => 'Refund completed',
        'refund_failed' => 'Refund failed',
    ],

    'webhook_event_kind' => [
        'payment_succeeded' => 'Payment succeeded',
        'payment_failed' => 'Payment failed',
        'payment_requires_action' => 'Payment needs action',
        'refund_succeeded' => 'Refund succeeded',
        'refund_failed' => 'Refund failed',
        'method_saved' => 'Payment method saved',
        'unknown' => 'Other',
    ],

    'webhook_outcome' => [
        'received' => 'Received',
        'processed' => 'Processed',
        'duplicate' => 'Duplicate',
        'ignored' => 'Ignored',
        'unmatched' => 'No matching payment',
        'invalid_signature' => 'Invalid signature',
        'replay_window' => 'Outside replay window',
        'amount_mismatch' => 'Amount mismatch',
        'failed' => 'Processing failed',
    ],

    'refund_status' => [
        'pending_approval' => 'Waiting for approval',
        'approved' => 'Approved',
        'processing' => 'At the gateway',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
    ],

    'fare_formula' => [
        '1' => 'Version 1: base + per km + per minute + stops + surcharges',
    ],

    'surcharge_mode' => [
        'fixed' => 'Fixed amount',
        'per_unit' => 'Per unit',
    ],

    'price_factor' => [
        'base' => 'Base fare',
        'distance' => 'Distance',
        'duration' => 'Duration',
        'stops' => 'Stops',
        'surcharge' => 'Surcharge',
        'minimum_fare' => 'Minimum fare',
    ],

    'fare_policy_state' => [
        'current' => 'Current',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'inactive' => 'Inactive',
    ],

    'entry_side' => [
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],
    'ledger_account_type' => [
        'customer_wallet' => 'Customer wallet',
        'provider_wallet' => 'Provider wallet',
        'platform_revenue' => 'Platform revenue',
        'promo_expense' => 'Promotions expense',
        'partner_receivable' => 'Partner receivable',
        'gateway_clearing' => 'Payment gateway clearing',
        'payout_clearing' => 'Payout clearing',
        'taxes_payable' => 'Taxes payable',
        'adjustments' => 'Adjustments',
    ],
    'journal_entry_type' => [
        'booking_settlement_cash' => 'Cash trip settlement',
        'booking_settlement_gateway' => 'Paid trip settlement',
        'tip' => 'Tip',
        'refund' => 'Refund',
        'payout_reserve' => 'Payout reserve',
        'payout_paid' => 'Payout paid',
        'adjustment' => 'Adjustment',
        'cash_settlement' => 'Cash commission settlement',
        'cancellation_fee' => 'Cancellation fee',
        'debt_settlement' => 'Wallet debt settled',
        'reversal' => 'Reversal',
    ],
    'payout_status' => [
        'requested' => 'Requested',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'processing' => 'Processing',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'reversed' => 'Reversed',
    ],
    'payout_method_type' => [
        'bank_account' => 'Bank account',
        'mobile_wallet' => 'Mobile wallet',
    ],
    'payout_batch_status' => [
        'open' => 'Open',
        'exported' => 'Exported',
        'completed' => 'Completed',
    ],
    'booking_adjustment_type' => [
        'extra_stop' => 'Extra stop',
        'waiting' => 'Waiting time',
        'toll_parking' => 'Toll or parking (at cost)',
        'scope_change' => 'Change of scope',
        'extra_helper' => 'Extra helper',
        'stairs' => 'Stairs beyond the agreed floors',
    ],
    'booking_adjustment_status' => [
        'pending' => 'Waiting for the other party',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'expired' => 'Expired',
        'voided' => 'Voided by operations',
    ],
    'approval_status' => [
        'pending' => 'Waiting for approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],
    'adjustment_direction' => [
        'credit' => 'Credit (increase balance)',
        'debit' => 'Debit (decrease balance)',
    ],
    'cash_reconciliation_status' => [
        'open' => 'Open',
        'settled' => 'Settled',
        'cancelled' => 'Cancelled',
    ],
    'cash_settlement_method' => [
        'cash_deposit' => 'Cash deposit',
        'bank_transfer' => 'Bank transfer',
        'mobile_wallet' => 'Mobile wallet',
    ],
    'commission_type' => [
        'percentage' => 'Percentage',
        'fixed' => 'Fixed amount',
        'hybrid' => 'Percentage plus fixed',
    ],
    'commission_rule_state' => [
        'current' => 'Current',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'inactive' => 'Inactive',
    ],
    'discount_funding' => [
        'platform' => 'Platform',
        'provider' => 'Provider',
        'partner' => 'Partner',
    ],
    'reconciliation_status' => [
        'ok' => 'Consistent',
        'mismatch' => 'Mismatch',
    ],
    'commission_scope' => [
        'service_category' => 'Service',
        'zone' => 'Zone',
        'provider_tier' => 'Provider tier',
        'campaign' => 'Campaign',
    ],

    'notification_channel' => [
        'push' => 'Push',
        'sms' => 'SMS',
        'email' => 'Email',
        'in_app' => 'In-app inbox',
    ],

    'notification_category' => [
        'booking' => 'Trips and bookings',
        'offers' => 'Offers and requests',
        'account' => 'Account and verification',
        'payments' => 'Payments and fees',
        'payouts' => 'Payouts',
        'promotions' => 'Coupons and offers',
        'support' => 'Support',
        'safety' => 'Safety',
        'marketing' => 'News and promotions',
        'system' => 'System',
    ],

    'notification_category_hint' => [
        'booking' => 'Trip progress, cancellations and changes to your bookings.',
        'offers' => 'Driver offers on your bookings, and new requests for drivers.',
        'account' => 'Application status, documents and account standing.',
        'payments' => 'Payments, refunds, wallet changes and cancellation fees.',
        'payouts' => 'Withdrawal requests and transfers.',
        'promotions' => 'Coupons applied to your bookings.',
        'support' => 'Messages and replies from our support team.',
        'safety' => 'Updates on safety reports.',
        'marketing' => 'Offers and news from us. Off until you turn it on.',
        'system' => 'Back-office messages.',
    ],

    'delivery_status' => [
        'queued' => 'Queued',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
    ],

    'notification_audience' => [
        'customer' => 'Customers',
        'provider' => 'Drivers',
        'both' => 'Customers and drivers',
        'admin' => 'Admins',
    ],

    'campaign_status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'cancelled' => 'Cancelled',
    ],

    'campaign_audience' => [
        'customers' => 'Customers',
        'providers' => 'Drivers',
        'everyone' => 'Everyone',
    ],

    'notification_event' => [
        'booking.message_received' => 'New chat message',
        'delivery.picked_up' => 'Parcel picked up',
        'delivery.arrived_dropoff' => 'Arrived at drop-off',
        'delivery.delivered' => 'Parcel delivered',
        'delivery.attempt_failed' => 'Delivery attempt failed',
        'delivery.attempt_resolved' => 'Delivery attempt resolved',
        'delivery.returned' => 'Parcel returned',
        'delivery.recipient_otp_locked' => 'Recipient code locked',
        'provider.application_submitted' => 'Driver application submitted',
        'provider.application_more_info' => 'Driver application needs changes',
        'provider.application_approved' => 'Driver application approved',
        'provider.application_rejected' => 'Driver application rejected',
        'provider.suspended' => 'Driver account suspended',
        'provider.reactivated' => 'Driver account reactivated',
        'document.expiring' => 'Document expiring soon',
        'document.expired' => 'Document expired',
        'booking.search_expired' => 'No driver found',
        'booking.offer_received' => 'Offer received',
        'booking.offer_updated' => 'Offer updated',
        'booking.provider_selected' => 'Driver confirmed',
        'booking.provider_heading' => 'Driver on the way',
        'booking.provider_arrived' => 'Driver arrived',
        'booking.trip_started' => 'Trip started',
        'booking.completed' => 'Trip completed',
        'booking.cancelled' => 'Booking cancelled',
        'booking.cancellation_fee_applied' => 'Cancellation fee applied',
        'booking.provider_cancelled_redispatch' => 'Driver cancelled, searching again',
        'booking.operations_update' => 'Support updated a booking',
        'booking.adjustment_requested' => 'Fare change requested',
        'booking.adjustment_answered' => 'Fare change answered',
        'request.new' => 'New request nearby',
        'offer.accepted' => 'Offer accepted',
        'offer.rejected' => 'Offer declined',
        'offer.expired' => 'Offer expired',
        'payment.pending' => 'Payment pending',
        'payment.succeeded' => 'Payment received',
        'payment.failed' => 'Payment failed',
        'payment.refunded' => 'Refund issued',
        'wallet.adjusted' => 'Wallet adjusted',
        'cash.debt_notice' => 'Cash amount due',
        'coupon.applied' => 'Coupon applied',
        'coupon.rejected' => 'Coupon not applied',
        'coupon.expiring' => 'Coupon expiring',
        'payout.requested' => 'Payout requested',
        'payout.approved' => 'Payout approved',
        'payout.rejected' => 'Payout rejected',
        'payout.paid' => 'Payout paid',
        'payout.failed' => 'Payout failed',
        'support.help_message' => 'Help message from support',
        'support.reply' => 'Support reply',
        'support.dispute_update' => 'Dispute update',
        'safety.case_update' => 'Safety case update',
        'marketing.campaign' => 'Marketing campaign',
        'safety.incident_raised' => 'Safety case raised (staff)',
        'admin.export_ready' => 'Export ready',
        'admin.export_failed' => 'Export failed',
        'review.hidden' => 'Review hidden',
        'review.appeal_decided' => 'Review appeal decided',
        'risk.signal_raised' => 'Risk signal raised (staff)',
    ],

    // @admin-module:enums

    'cancellation_stage' => [
        'searching' => 'Searching for a driver',
        'provider_selected' => 'Driver chosen',
        'heading_to_pickup' => 'Driver on the way',
        'arrived' => 'Driver arrived',
        'no_show' => 'No-show',
    ],

    'cancellation_fee_type' => [
        'none' => 'No fee',
        'fixed' => 'Fixed amount',
        'percent' => 'Percentage of the fare',
    ],

    'cancellation_fee_recipient' => [
        'provider' => 'Provider',
        'platform' => 'Platform',
    ],

    'cancellation_reason' => [
        'changed_mind' => 'I changed my mind',
        'wrong_pickup' => 'Wrong pickup location',
        'found_another_ride' => 'I found another ride',
        'driver_too_far' => 'The driver is too far',
        'driver_asked_to_cancel' => 'The driver asked me to cancel',
        'waited_too_long' => 'Waited too long',
        'driver_no_show' => 'The driver did not arrive',
        'customer_no_show' => 'Customer did not show up',
        'customer_unreachable' => 'Customer is unreachable',
        'vehicle_problem' => 'Vehicle problem',
        'emergency' => 'Emergency',
        'wrong_request_details' => 'Request details are wrong',
        'unsafe_pickup' => 'Unsafe pickup location',
        'other' => 'Other',
    ],

    'support_flag_reason' => [
        'recipient_otp_attempts_exceeded' => 'Too many wrong recipient codes',
        'delivery_attempt_escalated' => 'Failed delivery needs support',
        'pin_attempts_exceeded' => 'Too many wrong PIN attempts',
    ],

    'report_category' => [
        'safety' => 'Safety',
        'behaviour' => 'Behaviour',
        'fare_dispute' => 'Fare dispute',
        'route_or_delay' => 'Route or delay',
        'lost_item' => 'Lost item',
        'other' => 'Other',
    ],

    'report_status' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'moderation_status' => [
        'visible' => 'Visible',
        'hidden' => 'Hidden',
        'under_review' => 'Under review',
    ],

    'rating_tag' => [
        'professional' => 'Professional',
        'clean_vehicle' => 'Clean vehicle',
        'safe_driving' => 'Safe driving',
        'good_communication' => 'Good communication',
        'on_time' => 'On time',
        'careful_with_items' => 'Careful with items',
        'late' => 'Late',
        'unsafe_driving' => 'Unsafe driving',
        'rude' => 'Rude',
        'dirty_vehicle' => 'Dirty vehicle',
        'respectful' => 'Respectful',
        'clear_instructions' => 'Clear instructions',
        'ready_at_pickup' => 'Ready at pickup',
        'disrespectful' => 'Disrespectful',
        'not_ready_at_pickup' => 'Not ready at pickup',
    ],

    'provider_booking_action' => [
        'upload_proof' => 'Add proof',
        'mark_in_transit' => 'On the way to the recipient',
        'arrived_dropoff' => 'Arrived at drop-off',
        'verify_recipient_otp' => 'Enter recipient code',
        'resend_recipient_otp' => 'Send the code again',
        'report_delivery_attempt' => 'Report failed delivery',
        'resolve_delivery_attempt' => 'Decide after failed delivery',
        'chat' => 'Chat',
        'appeal' => 'Appeal fee',
        'confirm_cash_collected' => 'Confirm cash collected',
        'request_adjustment' => 'Change the fare',
        'view_receipt' => 'View the receipt',
        'acknowledge' => 'Confirm the job',
        'arrived' => 'I have arrived',
        'verify_start_pin' => 'Enter the trip PIN',
        'start' => 'Start the trip',
        'complete' => 'Complete the trip',
        'cancel' => 'Cancel the job',
        'rate_customer' => 'Rate the customer',
        'report' => 'Report a problem',
    ],

    'cancellation_policy_state' => [
        'current' => 'Current',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'inactive' => 'Disabled',
    ],

    'booking_override_action' => [
        'cancel_by_operations' => 'Cancel by operations',
        'extend_search' => 'Extend search',
        'redispatch_now' => 'Re-dispatch now',
        'force_transition' => 'Force status',
        'lift_pin_lock' => 'Lift PIN lock',
        'lift_recipient_otp_lock' => 'Lift recipient code lock',
        'resolve_delivery_attempt' => 'Resolve failed delivery',
    ],

    'operations_cancellation_fee' => [
        'waive' => 'Waive the fee',
        'apply_policy' => 'Apply the booking\'s cancellation policy',
    ],

    'offer_anomaly' => [
        'very_low' => 'Very low',
        'very_high' => 'Very high',
    ],

    'coupon_status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'active' => 'Active',
        'paused' => 'Paused',
        'exhausted' => 'Exhausted',
        'expired' => 'Expired',
        'archived' => 'Archived',
    ],

    'discount_type' => [
        'fixed' => 'Fixed amount',
        'percentage' => 'Percentage',
    ],

    'funding_source' => [
        'platform' => 'Platform',
        'provider' => 'Provider',
        'partner' => 'Partner',
        'split' => 'Split',
    ],

    'coupon_reservation_status' => [
        'reserved' => 'Reserved',
        'released' => 'Released',
        'committed' => 'Committed',
        'reversed' => 'Reversed',
    ],

    'coupon_redemption_status' => [
        'committed' => 'Committed',
        'reversed' => 'Reversed',
    ],

    'coupon_attempt_result' => [
        'eligible' => 'Eligible',
        'rejected' => 'Rejected',
        'reserved' => 'Reserved',
        'reservation_failed' => 'Reservation failed',
    ],

    'coupon_attempt_context' => [
        'validate' => 'Validation',
        'booking' => 'Booking',
        'automatic' => 'Automatic',
        'admin_test' => 'Admin test',
    ],

    'coupon_scope_type' => [
        'service_family' => 'Service family',
        'service_category' => 'Service',
        'vehicle_category' => 'Vehicle category',
        'city' => 'City',
        'zone' => 'Zone',
    ],

    'coupon_segment' => [
        'new' => 'New customers',
        'returning' => 'Returning customers',
    ],

    'coupon_reason' => [
        'eligible' => 'Eligible',
        'not_found' => 'Coupon not found',
        'not_active' => 'Coupon not active',
        'not_started' => 'Coupon not started',
        'expired' => 'Coupon expired',
        'exhausted' => 'Coupon exhausted',
        'category_not_eligible' => 'Service not eligible',
        'service_family_not_covered' => 'Service family not covered',
        'service_category_not_covered' => 'Service not covered',
        'service_excluded' => 'Service excluded',
        'vehicle_not_covered' => 'Vehicle not covered',
        'city_not_covered' => 'City not covered',
        'zone_not_covered' => 'Zone not covered',
        'payment_method_not_covered' => 'Payment method not covered',
        'new_customers_only' => 'New customers only',
        'returning_customers_only' => 'Returning customers only',
        'outside_schedule' => 'Outside the schedule',
        'below_minimum_fare' => 'Below the minimum fare',
        'customer_limit_reached' => 'Customer limit reached',
        'global_limit_reached' => 'Usage limit reached',
        'budget_exhausted' => 'Budget exhausted',
        'stacking_not_allowed' => 'Cannot be combined',
        'currency_mismatch' => 'Currency mismatch',
        'no_discount' => 'No discount',
    ],

    'ticket_category' => [
        'booking' => 'Booking',
        'fare_extra_charge' => 'Fare or extra charge',
        'payment_refund' => 'Payment or refund',
        'coupon' => 'Coupon',
        'driver_provider' => 'Driver or provider',
        'customer_conduct' => 'Customer conduct',
        'lost_item' => 'Lost item',
        'damaged_goods' => 'Damaged goods',
        'delivery_failure' => 'Delivery failure',
        'safety_harassment' => 'Safety or harassment',
        'account' => 'Account',
        'verification' => 'Verification',
        'payout' => 'Payout',
        'technical' => 'Technical issue',
        'other' => 'Other',
    ],

    'ticket_priority' => ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'],

    'ticket_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'waiting_user' => 'Waiting for the user',
        'escalated' => 'Escalated',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'ticket_group' => ['support' => 'Support', 'finance' => 'Finance', 'safety' => 'Safety', 'operations' => 'Operations'],

    'ticket_visibility' => ['public' => 'Visible to the user', 'internal' => 'Internal note'],

    'ticket_source' => ['app' => 'App', 'report' => 'Booking report', 'appeal' => 'Fee appeal', 'admin' => 'Admin'],

    'ticket_event_type' => [
        'created' => 'Ticket created',
        'reply' => 'Reply',
        'internal_note' => 'Internal note',
        'assigned' => 'Assigned',
        'unassigned' => 'Unassigned',
        'status_changed' => 'Status changed',
        'priority_changed' => 'Priority changed',
        'escalated' => 'Escalated',
        'resolved' => 'Resolved',
        'reopened' => 'Reopened',
        'linked' => 'Finance operation linked',
        'sla_breached' => 'SLA breached',
    ],
    'scope_item' => [
        'helpers' => 'Helpers',
        'stairs' => 'Stairs',
        'extra_stops' => 'Extra stops',
        'waiting_allowance' => 'Waiting allowance',
        'tolls' => 'Tolls',
        'parking' => 'Parking',
        'packing' => 'Packing',
        'assembly' => 'Assembly',
        'loading_equipment' => 'Loading equipment',
    ],
    'cost_treatment' => [
        'included' => 'Included in the fare',
        'at_cost_with_evidence' => 'At cost, with a receipt',
        'excluded' => 'Not included',
    ],
];
