<?php

namespace App\Exceptions;

/**
 * State conflict (409): stale lock_version, booking already assigned, request already in progress.
 */
class ConflictException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409;
    }
}
