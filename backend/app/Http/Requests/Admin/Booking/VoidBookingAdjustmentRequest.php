<?php

namespace App\Http\Requests\Admin\Booking;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Void a booking adjustment. Mandatory reason.
 */
class VoidBookingAdjustmentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'void';
    }

    protected function parameter(): string
    {
        return 'bookingAdjustment';
    }
}
