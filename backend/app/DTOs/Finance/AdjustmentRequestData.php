<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\AdjustmentDirection;

/**
 * An admin's request to change a wallet balance: which wallet (ledger account id), the direction, the amount (minor units), the
 * mandatory reason and an optional external reference. A second admin decides.
 */
final readonly class AdjustmentRequestData
{
    public function __construct(
        public int $accountId,
        public AdjustmentDirection $direction,
        public int $amountMinor,
        public string $reason,
        public ?string $reference,
        public string $idempotencyKey,
    ) {}
}
