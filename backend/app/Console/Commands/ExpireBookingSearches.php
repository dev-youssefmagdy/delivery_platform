<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingExpiryService;
use App\Services\Booking\ScheduledBookingReleaseService;
use Illuminate\Console\Command;

/**
 * `bookings:expire-searches` (every 5 s): ends search windows that ran out and opens the search of scheduled bookings
 * that are due. The delayed `ExpireBookingSearch` job does the same at the exact second; both are idempotent.
 */
class ExpireBookingSearches extends Command
{
    protected $signature = 'bookings:expire-searches';

    protected $description = 'Expire booking searches that ran out of time and release due scheduled bookings';

    public function handle(BookingExpiryService $expiry, ScheduledBookingReleaseService $release): int
    {
        $this->info('Expired: '.$expiry->sweep().', released: '.$release->releaseDue());

        return self::SUCCESS;
    }
}
