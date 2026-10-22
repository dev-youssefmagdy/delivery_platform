<?php

namespace App\DTOs\Payments;

use App\Models\Payment;

/**
 * A started (or resumed) online payment and what the app must open to complete it. The client secret exists only here
 * and in the encrypted payment meta; it is not logged and not part of any other response.
 */
final readonly class PaymentIntentResult
{
    public function __construct(
        public Payment $payment,
        public ?string $clientSecret = null,
        public ?string $actionUrl = null,
        public bool $replayed = false,
    ) {}
}
