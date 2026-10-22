<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingAdjustmentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * `bookings:expire-adjustments` (every minute): closes fare adjustment requests that the other party did not answer in
 * time. Idempotent; a request answered in the meantime is left alone (the service re-checks under the booking lock).
 */
class ExpireBookingAdjustments extends Command
{
    protected $signature = 'bookings:expire-adjustments';

    protected $description = 'Expire pending booking adjustment requests whose time ran out';

    public function handle(BookingAdjustmentService $adjustments): int
    {
        $this->info('Expired: '.$adjustments->expireDue(CarbonImmutable::now(), (int) config('booking.sweep_batch', 200)));

        return self::SUCCESS;
    }
}
