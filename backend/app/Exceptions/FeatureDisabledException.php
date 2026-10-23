<?php

namespace App\Exceptions;

/**
 * A feature behind a server flag is switched off for this caller (404 `feature_disabled`): the app shows no UI for it.
 */
class FeatureDisabledException extends DomainException
{
    public function getStatusCode(): int
    {
        return 404;
    }
}
