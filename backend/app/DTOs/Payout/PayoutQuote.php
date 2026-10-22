<?php

namespace App\DTOs\Payout;

/**
 * What a payout of an amount costs under the current settings.
 */
final readonly class PayoutQuote
{
    public function __construct(
        public int $amountMinor,
        public int $feeMinor,
        public int $netMinor,
        public bool $requiresDualApproval,
    ) {}
}
