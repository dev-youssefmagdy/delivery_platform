<?php

use App\Http\Controllers\Admin\Geography\CityController;
use App\Models\City;
use Illuminate\Support\Facades\Route;

/*
| Cities (service_areas.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('cities')->name('cities.')->controller(CityController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', City::class);
    Route::get('create', 'create')->name('create')->can('create', City::class);
    Route::post('/', 'store')->name('store')->can('create', City::class);
    Route::get('{city}', 'show')->whereNumber('city')->name('show')->can('view', 'city');
    Route::get('{city}/activity', 'activity')->whereNumber('city')->name('activity')->can('view', 'city');
    Route::get('{city}/edit', 'edit')->whereNumber('city')->name('edit')->can('update', 'city');
    Route::put('{city}', 'update')->whereNumber('city')->name('update')->can('update', 'city');
    Route::post('{city}/activate', 'activate')->whereNumber('city')->name('activate')->can('activate', 'city');
    Route::post('{city}/deactivate', 'deactivate')->whereNumber('city')->name('deactivate')->can('deactivate', 'city');
    Route::delete('{city}', 'destroy')->whereNumber('city')->name('destroy')->can('delete', 'city');
});
