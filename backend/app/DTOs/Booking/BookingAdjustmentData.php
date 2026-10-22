<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\BookingAdjustmentType;

/**
 * Input of a new adjustment request. `amountMinor` is the change in minor units (a waiting adjustment may omit it when
 * the policy has a per-minute rate: the server prices it); the client never sends a total. `evidence` is limited to a
 * reference (receipt number, note) and the reported minutes; files arrive with the proofs of P4.
 */
final readonly class BookingAdjustmentData
{
    /**
     * @param array<string, mixed> $details type specific facts (waiting minutes, stop address text)
     * @param array<string, mixed> $evidence
     */
    public function __construct(
        public BookingAdjustmentType $type,
        public ?int $amountMinor,
        public string $reason,
        public array $details = [],
        public array $evidence = [],
        public ?string $idempotencyKey = null,
    ) {}
}
