<?php

namespace App\Integrations\Payments\Data;

use Carbon\CarbonImmutable;

final readonly class GatewayTransaction
{
    public function __construct(
        public string $reference,
        public GatewayPaymentState $state,
        public int $amountMinor,
        public string $currency,
        public int $refundedMinor = 0,
        public ?CarbonImmutable $occurredAt = null,
    ) {}
}
