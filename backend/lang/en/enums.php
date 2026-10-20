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

    // @admin-module:enums
];
