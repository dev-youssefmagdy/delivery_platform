<?php

namespace App\Events\Booking;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking (re)starts looking for providers: it left `draft`, a provider cancelled and it searches again, or its
 * request changed. Dispatched after the transition commits; the listener starts dispatch (from wave 1, forgetting who
 * was notified when `restart` is set) and schedules the exact expiry of the search window.
 */
final class BookingEnteredSearch implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int $bookingId, public readonly bool $restart = false) {}
}
