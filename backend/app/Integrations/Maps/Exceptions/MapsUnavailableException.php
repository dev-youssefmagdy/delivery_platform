<?php

namespace App\Integrations\Maps\Exceptions;

use RuntimeException;
use Throwable;

/**
 * The maps provider is not configured, not reachable, rate limited, over quota or answered garbage. The message
 * never contains URLs, keys or request bodies.
 */
final class MapsUnavailableException extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, 0, $previous);
    }
}
