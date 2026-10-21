<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\NoOfferQueueDataTable;
use App\Http\Controllers\Admin\AdminController;

/**
 * The no-offer queue (bookings.view to look, the override permissions to act): searching bookings without offers for too
 * long and recent `expired_no_provider` bookings.
 */
final class NoOfferQueueController extends AdminController
{
    public function index(NoOfferQueueDataTable $table): mixed
    {
        return $table->render('admin.bookings.no-offer-queue', [
            'afterSeconds' => (int) config('booking.operations.no_offer_after_seconds', 120),
            'expiredHours' => (int) config('booking.operations.expired_window_hours', 24),
        ]);
    }
}
