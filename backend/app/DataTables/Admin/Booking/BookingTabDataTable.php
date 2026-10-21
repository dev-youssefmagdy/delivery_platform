<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\Models\Booking;
use LogicException;

/**
 * Base of the DataTables inside the booking show page tabs. The booking comes from the `{booking}` route parameter
 * (already authorized by the route's `can:` middleware); global search is off.
 */
abstract class BookingTabDataTable extends AdminDataTable
{
    protected bool $globalSearch = false;

    protected function booking(): Booking
    {
        $booking = $this->request()->route('booking');

        return $booking instanceof Booking ? $booking : throw new LogicException('Booking tab tables need a {booking} route parameter.');
    }
}
