<?php

namespace App\Exceptions;

/**
 * The actor is authenticated but the domain forbids this action in the current state (403).
 */
class ForbiddenActionException extends DomainException
{
    public function getStatusCode(): int
    {
        return 403;
    }
}
