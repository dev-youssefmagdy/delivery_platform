<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\BookingsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Booking;
use App\Services\Booking\BookingAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Bookings (bookings.view): the listing and the show page. Overrides live in BookingOverrideController, the lazy tabs in
 * BookingTabController. There is no create, edit or delete: bookings are made by customers and changed only through the
 * audited operator actions.
 */
final class BookingController extends AdminController
{
    public function __construct(private readonly BookingAdminService $bookings) {}

    public function index(BookingsDataTable $table): mixed
    {
        return $table->render('admin.bookings.index');
    }

    public function show(Booking $booking): View
    {
        $canViewPii = Gate::allows('viewPii', $booking);

        return view('admin.bookings.show', ['details' => $this->bookings->details($this->bookings->find($booking->uuid), $this->actor(), $canViewPii)]);
    }
}
