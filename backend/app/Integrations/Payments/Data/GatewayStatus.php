<?php

namespace App\Integrations\Payments\Data;

final readonly class GatewayStatus
{
    public function __construct(
        public GatewayPaymentState $state,
        public ?int $amountMinor = null,
        public ?string $currency = null,
        public ?int $refundedMinor = null,
        public ?string $failureCode = null,
    ) {}
}
