<?php

namespace App\Exceptions;

/**
 * A dependency (SMS gateway, ...) is down or not configured (503). Never exposes provider details.
 */
class ServiceUnavailableException extends DomainException
{
    public function getStatusCode(): int
    {
        return 503;
    }
}
