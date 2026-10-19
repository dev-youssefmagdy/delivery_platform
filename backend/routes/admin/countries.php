<?php

use App\Http\Controllers\Admin\Markets\CountryController;
use App\Models\Country;
use Illuminate\Support\Facades\Route;

/*
| Countries (markets.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('countries')->name('countries.')->controller(CountryController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Country::class);
    Route::get('create', 'create')->name('create')->can('create', Country::class);
    Route::post('/', 'store')->name('store')->can('create', Country::class);
    Route::get('{country}', 'show')->whereNumber('country')->name('show')->can('view', 'country');
    Route::get('{country}/activity', 'activity')->whereNumber('country')->name('activity')->can('view', 'country');
    Route::get('{country}/edit', 'edit')->whereNumber('country')->name('edit')->can('update', 'country');
    Route::put('{country}', 'update')->whereNumber('country')->name('update')->can('update', 'country');
    Route::delete('{country}', 'destroy')->whereNumber('country')->name('destroy')->can('delete', 'country');
});
