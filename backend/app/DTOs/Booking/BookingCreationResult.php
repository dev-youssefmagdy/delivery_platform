<?php

namespace App\DTOs\Booking;

use App\Models\Booking;

/**
 * The booking and whether it already existed (a replay of the same Idempotency-Key).
 */
final readonly class BookingCreationResult
{
    public function __construct(public Booking $booking, public bool $replayed = false) {}
}
