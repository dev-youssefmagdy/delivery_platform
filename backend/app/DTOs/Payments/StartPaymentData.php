<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentMethodType;

/**
 * What a customer chooses when paying online: a saved method (own uuid) or a method type, and the wallet phone for a
 * mobile-wallet payment. A tip adds its amount; the amount of a trip payment is never sent by the client (Plan I12).
 */
final readonly class StartPaymentData
{
    public function __construct(
        public ?string $paymentMethodId = null,
        public ?PaymentMethodType $methodType = null,
        public ?string $walletPhone = null,
        public ?int $tipAmountMinor = null,
    ) {}
}
