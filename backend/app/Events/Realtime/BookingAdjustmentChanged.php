<?php

namespace App\Events\Realtime;

/**
 * A fare adjustment of a booking was requested, approved, rejected, withdrawn, expired or voided (P3.5), delivered to
 * the customer's and the provider's own channels as `booking.adjustment_changed`. The other party sees a pending request
 * here and answers it in the app; clients refetch the adjustment list and the booking total.
 */
final class BookingAdjustmentChanged extends PartyEvent
{
    public function eventName(): string
    {
        return 'booking.adjustment_changed';
    }
}
