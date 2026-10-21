<?php

namespace App\Http\Requests\Admin\Booking;

/**
 * Re-dispatch now: dispatch starts over from the first wave. Only a reason is needed.
 */
class RedispatchBookingRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'manage';
    }
}
