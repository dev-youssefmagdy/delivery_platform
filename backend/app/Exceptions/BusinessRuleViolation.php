<?php

namespace App\Exceptions;

/**
 * A business rule rejected the request (422), e.g. `offer_expired`. Rendered like a validation error.
 */
class BusinessRuleViolation extends DomainException
{
    public function getStatusCode(): int
    {
        return 422;
    }
}
