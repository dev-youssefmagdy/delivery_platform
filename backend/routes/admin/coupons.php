<?php

use App\Http\Controllers\Admin\Coupon\CouponController;
use App\Models\Coupon;
use Illuminate\Support\Facades\Route;

/*
| Coupons (coupons.view / coupons.manage / coupons.export): campaigns with an eligibility builder, immutable financial versions,
| pause / resume / archive with a reason, redemptions / attempts / performance tabs, the "test eligibility" tool and the queued
| export. Coupons are archived, never deleted. Loaded by routes/admin.php.
*/
Route::prefix('coupons')->name('coupons.')->controller(CouponController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Coupon::class);
    Route::get('create', 'create')->name('create')->can('create', Coupon::class);
    Route::post('/', 'store')->name('store')->can('create', Coupon::class);
    Route::post('export', 'export')->name('export')->can('export', Coupon::class);
    Route::get('{coupon}', 'show')->whereUuid('coupon')->name('show')->can('view', 'coupon');
    Route::get('{coupon}/activity', 'activity')->whereUuid('coupon')->name('activity')->can('view', 'coupon');
    Route::get('{coupon}/tabs/redemptions', 'redemptions')->whereUuid('coupon')->name('tabs.redemptions')->can('view', 'coupon');
    Route::get('{coupon}/tabs/attempts', 'attempts')->whereUuid('coupon')->name('tabs.attempts')->can('view', 'coupon');
    Route::get('{coupon}/tabs/performance', 'performance')->whereUuid('coupon')->name('tabs.performance')->can('view', 'coupon');
    Route::post('{coupon}/test', 'test')->whereUuid('coupon')->name('test')->can('view', 'coupon');
    Route::get('{coupon}/edit', 'edit')->whereUuid('coupon')->name('edit')->can('update', 'coupon');
    Route::put('{coupon}', 'update')->whereUuid('coupon')->name('update')->can('update', 'coupon');
    Route::post('{coupon}/publish', 'publish')->whereUuid('coupon')->name('publish')->can('publish', 'coupon');
    Route::post('{coupon}/pause', 'pause')->whereUuid('coupon')->name('pause')->can('pause', 'coupon');
    Route::post('{coupon}/resume', 'resume')->whereUuid('coupon')->name('resume')->can('resume', 'coupon');
    Route::post('{coupon}/archive', 'archive')->whereUuid('coupon')->name('archive')->can('archive', 'coupon');
});

// Coupons tab of the customer page.
Route::get('customers/{customer:uuid}/tabs/coupons', [CouponController::class, 'customerTab'])->whereUuid('customer')->withTrashed()->name('customers.tabs.coupons')->can('view', 'customer')->can('viewAny', Coupon::class);
