<?php

use App\Http\Controllers\Admin\Catalog\VehicleCategoryController;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\Route;

/*
| Vehicle categories (vehicle_categories.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('vehicle-categories')->name('vehicle-categories.')->controller(VehicleCategoryController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', VehicleCategory::class);
    Route::get('create', 'create')->name('create')->can('create', VehicleCategory::class);
    Route::post('/', 'store')->name('store')->can('create', VehicleCategory::class);
    Route::get('{vehicleCategory}', 'show')->whereNumber('vehicleCategory')->name('show')->can('view', 'vehicleCategory');
    Route::get('{vehicleCategory}/activity', 'activity')->whereNumber('vehicleCategory')->name('activity')->can('view', 'vehicleCategory');
    Route::get('{vehicleCategory}/edit', 'edit')->whereNumber('vehicleCategory')->name('edit')->can('update', 'vehicleCategory');
    Route::put('{vehicleCategory}', 'update')->whereNumber('vehicleCategory')->name('update')->can('update', 'vehicleCategory');
    Route::post('{vehicleCategory}/activate', 'activate')->whereNumber('vehicleCategory')->name('activate')->can('activate', 'vehicleCategory');
    Route::post('{vehicleCategory}/deactivate', 'deactivate')->whereNumber('vehicleCategory')->name('deactivate')->can('deactivate', 'vehicleCategory');
    Route::delete('{vehicleCategory}', 'destroy')->whereNumber('vehicleCategory')->name('destroy')->can('delete', 'vehicleCategory');
});
