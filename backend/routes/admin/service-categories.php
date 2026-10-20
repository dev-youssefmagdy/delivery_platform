<?php

use App\Http\Controllers\Admin\Catalog\ServiceCategoryController;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Route;

/*
| Service categories (services.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('service-categories')->name('service-categories.')->controller(ServiceCategoryController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ServiceCategory::class);
    Route::get('create', 'create')->name('create')->can('create', ServiceCategory::class);
    Route::post('/', 'store')->name('store')->can('create', ServiceCategory::class);
    Route::get('{serviceCategory}', 'show')->whereNumber('serviceCategory')->name('show')->can('view', 'serviceCategory');
    Route::get('{serviceCategory}/versions', 'versions')->whereNumber('serviceCategory')->name('versions')->can('view', 'serviceCategory');
    Route::get('{serviceCategory}/activity', 'activity')->whereNumber('serviceCategory')->name('activity')->can('view', 'serviceCategory');
    Route::get('{serviceCategory}/edit', 'edit')->whereNumber('serviceCategory')->name('edit')->can('update', 'serviceCategory');
    Route::put('{serviceCategory}', 'update')->whereNumber('serviceCategory')->name('update')->can('update', 'serviceCategory');
    Route::put('{serviceCategory}/vehicle-categories', 'updateVehicleCategories')->whereNumber('serviceCategory')->name('vehicle-categories.update')->can('update', 'serviceCategory');
    Route::put('{serviceCategory}/form-schema', 'updateFormSchema')->whereNumber('serviceCategory')->name('form-schema.update')->can('update', 'serviceCategory');
    Route::post('{serviceCategory}/publish', 'publish')->whereNumber('serviceCategory')->name('publish')->can('publish', 'serviceCategory');
    Route::post('{serviceCategory}/activate', 'activate')->whereNumber('serviceCategory')->name('activate')->can('activate', 'serviceCategory');
    Route::post('{serviceCategory}/deactivate', 'deactivate')->whereNumber('serviceCategory')->name('deactivate')->can('deactivate', 'serviceCategory');
    Route::delete('{serviceCategory}', 'destroy')->whereNumber('serviceCategory')->name('destroy')->can('delete', 'serviceCategory');
});
