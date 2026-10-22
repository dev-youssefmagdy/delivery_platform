<?php

namespace App\Integrations\Payments\Data;

use App\Enums\Payments\WebhookEventKind;
use Carbon\CarbonImmutable;

/**
 * A callback whose signature was checked, in the platform's neutral shape. `eventId` is the processor's own id for the
 * event (stable across redeliveries); `payload` is the vendor JSON with anything secret removed.
 */
final readonly class VerifiedWebhook
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $eventId,
        public WebhookEventKind $kind,
        public CarbonImmutable $occurredAt,
        public ?string $gatewayReference = null,
        public ?int $amountMinor = null,
        public ?string $currency = null,
        public ?string $failureCode = null,
        public ?string $refundReference = null,
        public ?string $methodToken = null,
        public ?string $methodBrand = null,
        public ?string $methodLast4 = null,
        public ?string $setupReference = null,
        public array $payload = [],
    ) {}
}
