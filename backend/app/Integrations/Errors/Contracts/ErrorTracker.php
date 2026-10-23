<?php

namespace App\Integrations\Errors\Contracts;

use Throwable;

/**
 * Optional error-tracking adapter (SRS 23.4). Off unless `SENTRY_DSN` is set. It receives the exception and the correlation id only:
 * no request body, headers, cookies or user data are ever sent.
 */
interface ErrorTracker
{
    public function report(Throwable $exception, ?string $requestId = null): void;
}
