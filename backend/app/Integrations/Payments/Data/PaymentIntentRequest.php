<?php

namespace App\Integrations\Payments\Data;

use App\Enums\Payments\PaymentMethodType;
use App\Support\Money\Money;

/**
 * What a driver needs to start one online payment. `reference` is the platform's own payment uuid (merchant reference).
 * Customer details are the minimum a processor insists on; the phone is only given when a wallet payment needs it.
 */
final readonly class PaymentIntentRequest
{
    public function __construct(
        public string $reference,
        public Money $amount,
        public PaymentMethodType $method,
        public string $description,
        public ?string $savedMethodToken = null,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $returnUrl = null,
    ) {}
}
