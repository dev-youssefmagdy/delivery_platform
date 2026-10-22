<?php

namespace App\Integrations\Payments\Data;

use App\Enums\Payments\PaymentMethodType;

final readonly class SetupIntentRequest
{
    public function __construct(
        public string $reference,
        public PaymentMethodType $method,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $returnUrl = null,
    ) {}
}
