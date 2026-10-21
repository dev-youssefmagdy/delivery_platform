<?php

namespace App\Services\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

/**
 * Notification hook of the booking module. Until the notification platform (P3.6) it writes to the log channel only:
 * nothing is sent to the customer, and the log lines say so. The customer still learns the outcome from the app
 * (status endpoint, realtime event); P3.6 replaces the bodies with push / in-app notifications.
 */
class BookingNotifier
{
    public function searchExpired(Booking $booking): void
    {
        Log::info('booking.search_expired (notification stub, nothing sent)', [
            'booking' => $booking->uuid,
            'customer' => $booking->customer_id,
        ]);
    }
}
