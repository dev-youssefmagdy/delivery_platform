<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\BookingAdjustmentsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Booking\VoidBookingAdjustmentRequest;
use App\Models\BookingAdjustmentRequest;
use App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Booking\BookingAdjustmentService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Booking fare adjustments (bookings.view / bookings.manage): listing, detail and the audited void. Operations never
 * approve or reject for a party; the parties answer in their apps.
 */
final class BookingAdjustmentController extends AdminController
{
    public function __construct(private readonly BookingAdjustmentService $adjustments) {}

    public function index(BookingAdjustmentsDataTable $table, BookingAdjustmentRepositoryInterface $repository): mixed
    {
        return $table->render('admin.booking-adjustments.index', ['pending' => $repository->pendingCount()]);
    }

    public function show(string $bookingAdjustment, BookingAdjustmentRepositoryInterface $repository, AuditTrailService $trail): View
    {
        $adjustment = $repository->findByUuid($bookingAdjustment) ?? abort(404);
        $adjustment->load('booking');

        return view('admin.booking-adjustments.show', ['adjustment' => $adjustment, 'logs' => $trail->latestFor($adjustment)]);
    }

    public function void(VoidBookingAdjustmentRequest $request, BookingAdjustmentRequest $bookingAdjustment): AdminFormResponse
    {
        $this->adjustments->voidByAdmin($this->actor(), $bookingAdjustment, (string) $request->reason());

        return $this->formResponse(__('admin.booking_adjustments.voided'))->reloadTable(BookingAdjustmentsDataTable::TABLE_ID)->reload();
    }
}
