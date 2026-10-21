<?php

namespace App\DTOs\Booking;

use App\Models\Booking;

/**
 * Outcome of a provider trip action. `replayed` is true when the booking was already in the requested state (a retry
 * after a lost response), so nothing was written.
 */
final readonly class TripActionResult
{
    public function __construct(public Booking $booking, public bool $replayed = false) {}
}
