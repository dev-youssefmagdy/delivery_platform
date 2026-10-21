<?php

namespace App\Jobs\Booking;

use App\Enums\Common\QueueName;
use App\Services\Booking\BookingExpiryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Delayed to the exact end of a booking's search window (critical queue). The scheduler sweep
 * (`bookings:expire-searches`) is the backstop for a lost job; both end the search only if the window really is over,
 * and ending it twice is a no-op, so neither needs the client.
 */
class ExpireBookingSearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $bookingId)
    {
        $this->onQueue(QueueName::Critical->value);
    }

    public function handle(BookingExpiryService $expiry): void
    {
        $expiry->expire($this->bookingId);
    }
}
