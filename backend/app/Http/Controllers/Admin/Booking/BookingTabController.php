<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\BookingAuditDataTable;
use App\DataTables\Admin\Booking\BookingDispatchDataTable;
use App\DataTables\Admin\Booking\BookingOffersDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Booking;
use App\Services\Booking\BookingDetailTabsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Lazy-loaded tabs of the booking show page. Tables are their own Yajra DataTables served from their own URLs (a normal
 * request returns the HTML fragment, the DataTables AJAX call the rows); the others are small server-rendered fragments.
 */
final class BookingTabController extends AdminController
{
    public function __construct(private readonly BookingDetailTabsService $tabs) {}

    public function timeline(Booking $booking): View
    {
        return view('admin.bookings.tabs.timeline', ['items' => $this->tabs->timeline($booking)]);
    }

    public function offers(BookingOffersDataTable $table, Booking $booking): mixed
    {
        return $table->render('admin.bookings.tabs.table', ['intro' => __('admin.bookings.offers.intro')]);
    }

    public function dispatch(BookingDispatchDataTable $table, Booking $booking): mixed
    {
        return $table->render('admin.bookings.tabs.table', ['intro' => __('admin.bookings.dispatch.intro')]);
    }

    public function snapshots(Booking $booking): View
    {
        return view('admin.bookings.tabs.snapshots', ['snapshots' => $this->tabs->snapshots($booking, Gate::allows('viewPii', $booking))]);
    }

    public function safety(Booking $booking): View
    {
        return view('admin.bookings.tabs.safety', ['safety' => $this->tabs->safety($booking), 'booking' => $booking]);
    }

    public function ratings(Booking $booking): View
    {
        return view('admin.bookings.tabs.ratings', ['ratings' => $this->tabs->ratings($booking)]);
    }

    public function audit(BookingAuditDataTable $table, Booking $booking): mixed
    {
        return $table->render('admin.bookings.tabs.table', ['intro' => __('admin.bookings.audit.intro')]);
    }
}
