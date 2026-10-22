<?php

namespace App\Integrations\Payments\Data;

use App\Enums\Payments\PaymentStatus;
use Carbon\CarbonImmutable;

/**
 * The processor's answer to "start this payment": its own reference and what the app must do next. Never `paid`.
 */
final readonly class GatewayIntent
{
    public function __construct(
        public string $reference,
        public PaymentStatus $status = PaymentStatus::Pending,
        public ?string $clientSecret = null,
        public ?string $actionUrl = null,
        public ?CarbonImmutable $expiresAt = null,
        /** @var array<string, scalar|null> driver facts kept on the payment (never secrets) */
        public array $meta = [],
    ) {}
}
