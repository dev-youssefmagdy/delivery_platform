<?php

namespace App\Http\Requests\Admin\Booking;

/**
 * Lift the recipient-code lock of a parcel booking (bookings.pin_override): a mandatory reason, audited, parties informed.
 */
class LiftRecipientOtpLockRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'liftRecipientOtpLock';
    }
}
