<?php

namespace App\Exceptions;

/**
 * A business-level cooldown (429), for example "wait before requesting another OTP". Carries `retry_after` seconds
 * in `meta` and the `Retry-After` header, like the framework throttle.
 */
class RateLimitedException extends DomainException
{
    public static function afterSeconds(string $code, int $seconds, ?string $field = null): static
    {
        return static::make($code, $field, ['retry_after' => max(1, $seconds), 'seconds' => max(1, $seconds)]);
    }

    public function getStatusCode(): int
    {
        return 429;
    }

    public function getHeaders(): array
    {
        return isset($this->context['retry_after']) ? ['Retry-After' => (string) $this->context['retry_after']] : [];
    }
}
