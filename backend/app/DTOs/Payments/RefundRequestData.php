<?php

namespace App\DTOs\Payments;

/**
 * An admin's refund request: the amount in minor units, the mandatory reason and the idempotency key generated with the
 * form (a replayed submit returns the first request instead of creating a second one).
 */
final readonly class RefundRequestData
{
    public function __construct(public int $amountMinor, public string $reason, public string $idempotencyKey) {}
}
