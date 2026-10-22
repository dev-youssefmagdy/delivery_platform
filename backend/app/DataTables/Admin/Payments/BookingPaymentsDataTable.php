<?php

namespace App\DataTables\Admin\Payments;

use App\DTOs\Payments\PaymentFilterData;
use App\Models\Booking;
use LogicException;

/**
 * Payment tab of the booking page: the booking's payments (trip attempts and tips). The booking comes from the
 * `{booking}` route parameter, already authorized by the route.
 */
final class BookingPaymentsDataTable extends PaymentsDataTable
{
    public const TABLE_ID = 'booking-payments-table';

    protected function scoped(PaymentFilterData $filters): PaymentFilterData
    {
        $booking = $this->request()->route('booking');

        return $booking instanceof Booking ? $filters->forBooking($booking->id) : throw new LogicException('The booking payments tab needs a {booking} route parameter.');
    }
}
