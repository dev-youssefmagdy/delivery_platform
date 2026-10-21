<?php

namespace App\Listeners\Booking;

use App\Events\Booking\BookingEnteredSearch;
use App\Jobs\Booking\ExpireBookingSearch;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Booking\DispatchableBookingSource;
use App\Services\Dispatch\DispatchService;

/**
 * When a booking enters the search phase: starts dispatch (wave 1 on the critical queue) and schedules the exact
 * end of the search window. Runs after the transition committed; if the booking already left the search phase
 * (cancelled in between) nothing starts.
 */
final class StartBookingDispatch
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly DispatchableBookingSource $source,
        private readonly DispatchService $dispatch,
    ) {}

    public function handle(BookingEnteredSearch $event): void
    {
        $booking = $this->bookings->find($event->bookingId);

        if (! $booking instanceof Booking || ! $booking->status->isSearching()) {
            return;
        }

        $request = $this->source->fromBooking($booking);

        if ($event->restart) {
            $this->dispatch->restart($request);
        } else {
            $this->dispatch->start($request);
        }

        if ($booking->search_expires_at !== null) {
            ExpireBookingSearch::dispatch($booking->id)->delay($booking->search_expires_at);
        }
    }
}
