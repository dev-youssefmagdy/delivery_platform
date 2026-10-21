<?php

namespace App\Http\Requests\Admin\Booking;

/**
 * Lift the PIN lock of a booking (bookings.pin_override): a mandatory reason, audited, parties informed.
 */
class LiftPinLockRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'liftPinLock';
    }
}
