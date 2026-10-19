<?php

/*
| API and AJAX envelope messages. `errors.{code}` keys match the stable machine codes in `errors._code`
| (docs/API.md). Never put internal details in these messages.
*/
return [
    'ok' => 'Request completed successfully.',
    'created' => 'Created successfully.',

    'health' => [
        'ok' => 'All services are operational.',
        'failing' => 'Some services are currently unavailable.',
    ],

    'http' => [
        '400' => 'The request could not be processed.',
        '401' => 'Please sign in first.',
        '403' => 'You are not allowed to perform this action.',
        '404' => 'The requested item was not found.',
        '409' => 'The request conflicts with the current state. Please refresh and try again.',
        '422' => 'The submitted data is invalid.',
        '429' => 'Too many requests. Please try again later.',
        '500' => 'Something went wrong. Please try again later.',
    ],

    'errors' => [
        'validation_failed' => 'The submitted data is invalid.',
        'unauthenticated' => 'Please sign in first.',
        'forbidden' => 'You are not allowed to perform this action.',
        'not_found' => 'The requested item was not found.',
        'method_not_allowed' => 'This request method is not supported.',
        'session_expired' => 'Your session has expired. Please reload the page.',
        'too_many_requests' => 'Too many requests. Please try again in :seconds seconds.',
        'service_unavailable' => 'The service is temporarily unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
        'idempotency_key_required' => 'The Idempotency-Key header is required for this request.',
        'idempotency_key_invalid' => 'The Idempotency-Key header must be a valid UUID.',
        'idempotency_key_reused' => 'This idempotency key was already used with a different payload.',
        'idempotency_request_in_progress' => 'An identical request is already in progress. Please wait.',
        'stale_version' => 'This record was changed by someone else. Reload the page and try again.',
        'export_not_ready' => 'This export is not available for download.',
        'export_failed' => 'The export could not be generated.',
        'code_taken' => 'This code is already in use.',
        'demo_item_archived' => 'Archived items cannot be changed.',
        'invalid_credentials' => 'These credentials do not match our records.',
        'mfa_invalid_code' => 'The verification code is not valid.',
        'mfa_session_expired' => 'The verification step expired. Please sign in again.',
        'mfa_setup_expired' => 'The setup session expired. Reload the page to get a new QR code.',
        'mfa_already_enabled' => 'Two-step verification is already enabled.',
        'mfa_not_enabled' => 'Two-step verification is not enabled.',
        'mfa_required_for_role' => 'Two-step verification is required for your role and cannot be turned off.',
        'mfa_enrolment_required' => 'Set up two-step verification before using the admin panel.',
        'password_incorrect' => 'The password is incorrect.',
        'password_reset_invalid' => 'This password reset link is invalid or has expired.',
        'email_taken' => 'This email address is already used by another admin.',
        'cannot_modify_self' => 'You cannot do this to your own account.',
        'last_super_admin' => 'The last active Super Admin cannot be suspended, deleted or demoted.',
        'cannot_remove_own_super_admin' => 'You cannot remove the Super Admin role from yourself.',
        'super_admin_only' => 'Only a Super Admin can grant or remove the Super Admin role.',
        'role_exceeds_own_permissions' => 'You cannot grant permissions that you do not have yourself.',
        'role_name_taken' => 'This role identifier is already in use.',
        'role_system_protected' => 'System roles cannot be deleted.',
        'role_in_use' => 'This role is still assigned to admins.',
        'role_super_admin_protected' => 'The Super Admin role cannot be edited.',
        'admin_already_suspended' => 'This admin is already suspended.',
        'admin_already_active' => 'This admin is already active.',
    ],
];
