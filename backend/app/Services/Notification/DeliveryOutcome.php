<?php

namespace App\Services\Notification;

/**
 * What a channel reported for one attempt: accepted, skipped (nothing to send to or the channel is not configured), a
 * permanent failure (never retried) or a temporary one (retried with back-off).
 */
final readonly class DeliveryOutcome
{
    private function __construct(
        public string $kind,
        public ?string $provider = null,
        public ?string $messageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function sent(string $provider, ?string $messageId = null): self
    {
        return new self('sent', $provider, $messageId);
    }

    public static function skipped(string $code, ?string $provider = null): self
    {
        return new self('skipped', $provider, errorCode: $code);
    }

    public static function failed(string $code, string $message, ?string $provider = null): self
    {
        return new self('failed', $provider, errorCode: $code, errorMessage: mb_substr($message, 0, 250));
    }

    public static function retry(string $code, string $message, ?string $provider = null): self
    {
        return new self('retry', $provider, errorCode: $code, errorMessage: mb_substr($message, 0, 250));
    }
}
