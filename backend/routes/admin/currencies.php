<?php

use App\Http\Controllers\Admin\Markets\CurrencyController;
use App\Models\Currency;
use Illuminate\Support\Facades\Route;

/*
| Currencies (markets.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('currencies')->name('currencies.')->controller(CurrencyController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Currency::class);
    Route::get('create', 'create')->name('create')->can('create', Currency::class);
    Route::post('/', 'store')->name('store')->can('create', Currency::class);
    Route::get('{currency}', 'show')->whereNumber('currency')->name('show')->can('view', 'currency');
    Route::get('{currency}/activity', 'activity')->whereNumber('currency')->name('activity')->can('view', 'currency');
    Route::get('{currency}/edit', 'edit')->whereNumber('currency')->name('edit')->can('update', 'currency');
    Route::put('{currency}', 'update')->whereNumber('currency')->name('update')->can('update', 'currency');
    Route::delete('{currency}', 'destroy')->whereNumber('currency')->name('destroy')->can('delete', 'currency');
});
