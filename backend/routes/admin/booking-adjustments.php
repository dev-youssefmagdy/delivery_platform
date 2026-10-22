<?php

use App\Http\Controllers\Admin\Booking\BookingAdjustmentController;
use App\Models\BookingAdjustmentRequest;
use Illuminate\Support\Facades\Route;

/*
| Booking fare adjustments (bookings.view / bookings.manage). Addressed by uuid. Loaded by routes/admin.php.
*/
Route::prefix('booking-adjustments')->name('booking-adjustments.')->controller(BookingAdjustmentController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', BookingAdjustmentRequest::class);
    Route::get('{bookingAdjustment}', 'show')->whereUuid('bookingAdjustment')->name('show')->can('viewAny', BookingAdjustmentRequest::class);
    Route::post('{bookingAdjustment:uuid}/void', 'void')->whereUuid('bookingAdjustment')->name('void')->can('void', 'bookingAdjustment');
});
