<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CancellationReason;

/**
 * Input of a cancellation by the customer or the provider. `acknowledgedFeeMinor` is the fee the customer saw in the
 * preview and agreed to; the commit refuses to apply a different one (the decision is recomputed under the lock).
 */
final readonly class CancelBookingData
{
    public function __construct(
        public CancellationReason $reason,
        public ?string $note = null,
        public bool $preview = false,
        public ?int $acknowledgedFeeMinor = null,
        public ?string $idempotencyKey = null,
    ) {}
}
