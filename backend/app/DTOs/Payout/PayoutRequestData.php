<?php

namespace App\DTOs\Payout;

/**
 * A provider's withdrawal request. The fee and the net amount are the server's (settings), never the client's.
 */
final readonly class PayoutRequestData
{
    public function __construct(
        public int $amountMinor,
        public string $methodUuid,
        public string $idempotencyKey,
    ) {}
}
