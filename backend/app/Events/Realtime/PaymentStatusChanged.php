<?php

namespace App\Events\Realtime;

/**
 * A payment of a booking changed state, delivered to the customer's and the provider's own channels as
 * `payment.status_changed`. Carries ids and statuses only (never amounts or gateway data); clients refetch the booking.
 */
final class PaymentStatusChanged extends PartyEvent
{
    public function eventName(): string
    {
        return 'payment.status_changed';
    }
}
