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
    ],
];
