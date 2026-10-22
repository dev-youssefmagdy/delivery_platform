<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\CashSettlementMethod;

/**
 * The money the platform received from the provider for its cash commission.
 */
final readonly class CashSettlementData
{
    public function __construct(
        public int $amountMinor,
        public CashSettlementMethod $method,
        public string $reference,
        public string $reason,
    ) {}
}
