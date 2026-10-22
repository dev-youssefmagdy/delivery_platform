<?php

use App\Http\Controllers\Admin\Booking\BookingController;
use App\Http\Controllers\Admin\Booking\BookingOverrideController;
use App\Http\Controllers\Admin\Booking\BookingTabController;
use App\Http\Controllers\Admin\Booking\NoOfferQueueController;
use App\Http\Controllers\Admin\Booking\OfferMonitorController;
use App\Http\Controllers\Admin\Booking\OperationsMapController;
use App\Models\Booking;
use App\Models\BookingOffer;
use Illuminate\Support\Facades\Route;

/*
| Booking operations (P2.8; bookings.view / manage / cancel / assign_override / pin_override / view_pii, offers.view).
| Loaded by routes/admin.php. Bookings are addressed by uuid. There is no create, edit or delete route: bookings come from
| customers and change only through the audited operator overrides below, which go through BookingTransitionService.
*/
Route::prefix('bookings')->name('bookings.')->group(function (): void {
    Route::controller(BookingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index')->can('viewAny', Booking::class);
    });

    Route::get('no-offer-queue', [NoOfferQueueController::class, 'index'])->name('no-offer-queue')->can('viewAny', Booking::class);

    Route::get('{booking:uuid}', [BookingController::class, 'show'])->whereUuid('booking')->name('show')->can('view', 'booking');

    Route::controller(BookingTabController::class)->prefix('{booking:uuid}/tabs')->name('tabs.')->whereUuid('booking')->group(function (): void {
        Route::get('timeline', 'timeline')->name('timeline')->can('view', 'booking');
        Route::get('offers', 'offers')->name('offers')->can('view', 'booking');
        Route::get('dispatch', 'dispatch')->name('dispatch')->can('view', 'booking');
        Route::get('snapshots', 'snapshots')->name('snapshots')->can('view', 'booking');
        Route::get('safety', 'safety')->name('safety')->can('view', 'booking');
        Route::get('ratings', 'ratings')->name('ratings')->can('view', 'booking');
        Route::get('adjustments', 'adjustments')->name('adjustments')->can('view', 'booking');
        Route::get('receipt', 'receipt')->name('receipt')->can('view', 'booking');
        Route::get('audit', 'audit')->name('audit')->can('view', 'booking');
    });

    Route::controller(BookingOverrideController::class)->prefix('{booking:uuid}')->whereUuid('booking')->group(function (): void {
        // Form fragments for the global modal; each ability is checked again inside the action.
        Route::get('override-forms/{form}', 'form')->where('form', '[a-z_]+')->name('override-form')->can('view', 'booking');
        Route::post('cancel', 'cancel')->name('cancel')->can('cancelByOperations', 'booking');
        Route::post('extend-search', 'extendSearch')->name('extend-search')->can('manage', 'booking');
        Route::post('redispatch', 'redispatch')->name('redispatch')->can('manage', 'booking');
        Route::post('force-transition', 'forceTransition')->name('force-transition')->can('forceTransition', 'booking');
        Route::post('lift-pin-lock', 'liftPinLock')->name('lift-pin-lock')->can('liftPinLock', 'booking');
        Route::post('contact', 'contact')->name('contact')->can('manage', 'booking');
    });
});

Route::prefix('operations')->name('operations.')->group(function (): void {
    Route::get('map', [OperationsMapController::class, 'show'])->name('map')->can('viewAny', Booking::class);
    Route::get('map/snapshot', [OperationsMapController::class, 'snapshot'])->name('map.snapshot')->can('viewAny', Booking::class);
});

Route::get('offers', [OfferMonitorController::class, 'index'])->name('offers.index')->can('viewAny', BookingOffer::class);
