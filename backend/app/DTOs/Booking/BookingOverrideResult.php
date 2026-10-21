<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\BookingOverrideAction;
use App\Models\Booking;

/**
 * Outcome of an operator override: the booking as it is now and a short machine-readable summary (also in the audit entry).
 */
final readonly class BookingOverrideResult
{
    /**
     * @param array<string, mixed> $summary
     */
    public function __construct(
        public Booking $booking,
        public BookingOverrideAction $action,
        public array $summary = [],
    ) {}
}
