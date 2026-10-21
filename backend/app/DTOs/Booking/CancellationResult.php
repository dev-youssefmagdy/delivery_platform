<?php

namespace App\DTOs\Booking;

use App\Models\Booking;

/**
 * What a cancellation call produced: the decision, the booking as it is now, whether the booking went back to
 * searching (provider cancel with automatic re-dispatch) and whether this was a preview or a replay.
 */
final readonly class CancellationResult
{
    public function __construct(
        public Booking $booking,
        public CancellationDecision $decision,
        public bool $committed,
        public bool $redispatched = false,
        public bool $replayed = false,
    ) {}
}
