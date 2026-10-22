<?php

namespace App\Integrations\Payments\Data;

use App\Support\Money\Money;

final readonly class RefundRequest
{
    public function __construct(
        public string $paymentReference,
        /** the platform's refund uuid: lets the processor deduplicate a retried call where it supports it */
        public string $refundReference,
        public Money $amount,
        public string $reason,
        public ?string $transactionId = null,
    ) {}
}
