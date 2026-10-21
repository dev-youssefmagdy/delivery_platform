<?php

use App\Http\Controllers\Admin\Customers\CustomerController;
use App\Http\Controllers\Admin\Customers\CustomerTabController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
| Customers (customers.view / view_pii / update / suspend / export / process_deletion). Loaded by routes/admin.php.
| Customers are addressed by uuid and stay reachable after anonymisation (withTrashed). There is no create, edit or
| delete route: customers cannot be hard-deleted from the panel.
*/
Route::prefix('customers')->name('customers.')->group(function (): void {
    Route::controller(CustomerController::class)->group(function (): void {
        Route::get('/', 'index')->name('index')->can('viewAny', User::class);
        Route::post('export', 'export')->name('export')->can('export', User::class);
        Route::get('{customer:uuid}', 'show')->whereUuid('customer')->withTrashed()->name('show')->can('view', 'customer');
        Route::post('{customer:uuid}/reveal', 'reveal')->whereUuid('customer')->withTrashed()->name('reveal')->can('viewPii', 'customer');
        Route::post('{customer:uuid}/suspend', 'suspend')->whereUuid('customer')->withTrashed()->name('suspend')->can('suspend', 'customer');
        Route::post('{customer:uuid}/reactivate', 'reactivate')->whereUuid('customer')->withTrashed()->name('reactivate')->can('reactivate', 'customer');
        Route::post('{customer:uuid}/restrict', 'restrict')->whereUuid('customer')->withTrashed()->name('restrict')->can('restrict', 'customer');
        Route::post('{customer:uuid}/messages', 'sendMessage')->whereUuid('customer')->withTrashed()->name('messages.store')->can('sendMessage', 'customer');
        Route::post('{customer:uuid}/export-data', 'exportData')->whereUuid('customer')->withTrashed()->name('export-data')->can('exportData', 'customer');
        Route::post('{customer:uuid}/deletion-requests/{deletionRequest}/approve', 'approveDeletion')->whereUuid(['customer', 'deletionRequest'])->withTrashed()->name('deletion-requests.approve')->can('processDeletion', 'customer');
        Route::post('{customer:uuid}/deletion-requests/{deletionRequest}/reject', 'rejectDeletion')->whereUuid(['customer', 'deletionRequest'])->withTrashed()->name('deletion-requests.reject')->can('processDeletion', 'customer');
    });

    Route::controller(CustomerTabController::class)->prefix('{customer:uuid}/tabs')->name('tabs.')->whereUuid('customer')->group(function (): void {
        Route::get('bookings', 'bookings')->withTrashed()->name('bookings')->can('view', 'customer');
        Route::get('ratings', 'ratings')->withTrashed()->name('ratings')->can('view', 'customer');
        Route::get('devices', 'devices')->withTrashed()->name('devices')->can('view', 'customer');
        Route::get('consents', 'consents')->withTrashed()->name('consents')->can('view', 'customer');
        Route::get('saved-places', 'savedPlaces')->withTrashed()->name('saved-places')->can('viewPii', 'customer');
        Route::get('deletion-requests', 'deletionRequests')->withTrashed()->name('deletion-requests')->can('view', 'customer');
        Route::get('messages', 'messages')->withTrashed()->name('messages')->can('view', 'customer');
        Route::get('audit', 'audit')->withTrashed()->name('audit')->can('view', 'customer');
    });
});
