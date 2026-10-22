<?php

namespace App\Integrations\Push\Exceptions;

use RuntimeException;

/**
 * The push provider refused or could not be reached. A permanent failure (invalid or unregistered token, malformed
 * message) is never retried; an invalid token additionally means the stored token must be forgotten.
 */
class PushDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'push_failed',
        private readonly bool $permanent = false,
        private readonly bool $invalidToken = false,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function permanent(): bool
    {
        return $this->permanent;
    }

    public function invalidToken(): bool
    {
        return $this->invalidToken;
    }

    public static function unregistered(string $message = 'The device token is no longer valid.'): self
    {
        return new self($message, 'invalid_token', permanent: true, invalidToken: true);
    }
}
