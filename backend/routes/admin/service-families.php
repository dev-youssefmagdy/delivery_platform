<?php

use App\Http\Controllers\Admin\Catalog\ServiceFamilyController;
use App\Models\ServiceFamily;
use Illuminate\Support\Facades\Route;

/*
| Service families (services.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('service-families')->name('service-families.')->controller(ServiceFamilyController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ServiceFamily::class);
    Route::get('create', 'create')->name('create')->can('create', ServiceFamily::class);
    Route::post('/', 'store')->name('store')->can('create', ServiceFamily::class);
    Route::get('{serviceFamily}', 'show')->whereNumber('serviceFamily')->name('show')->can('view', 'serviceFamily');
    Route::get('{serviceFamily}/activity', 'activity')->whereNumber('serviceFamily')->name('activity')->can('view', 'serviceFamily');
    Route::get('{serviceFamily}/edit', 'edit')->whereNumber('serviceFamily')->name('edit')->can('update', 'serviceFamily');
    Route::put('{serviceFamily}', 'update')->whereNumber('serviceFamily')->name('update')->can('update', 'serviceFamily');
    Route::post('{serviceFamily}/activate', 'activate')->whereNumber('serviceFamily')->name('activate')->can('activate', 'serviceFamily');
    Route::post('{serviceFamily}/deactivate', 'deactivate')->whereNumber('serviceFamily')->name('deactivate')->can('deactivate', 'serviceFamily');
    Route::delete('{serviceFamily}', 'destroy')->whereNumber('serviceFamily')->name('destroy')->can('delete', 'serviceFamily');
});
