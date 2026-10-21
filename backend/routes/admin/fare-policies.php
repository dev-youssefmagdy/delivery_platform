<?php

use App\Http\Controllers\Admin\Pricing\FarePolicyController;
use App\Models\FarePolicy;
use Illuminate\Support\Facades\Route;

/*
| Fare policies (fare_policies.manage): the reference-price configuration per service category and zone, plus a
| read-only calculator. Loaded by routes/admin.php.
*/
Route::prefix('fare-policies')->name('fare-policies.')->controller(FarePolicyController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', FarePolicy::class);
    Route::get('create', 'create')->name('create')->can('create', FarePolicy::class);
    Route::post('/', 'store')->name('store')->can('create', FarePolicy::class);
    Route::get('{farePolicy}', 'show')->whereNumber('farePolicy')->name('show')->can('view', 'farePolicy');
    Route::get('{farePolicy}/activity', 'activity')->whereNumber('farePolicy')->name('activity')->can('view', 'farePolicy');
    Route::post('{farePolicy}/preview', 'preview')->whereNumber('farePolicy')->name('preview')->can('view', 'farePolicy');
    Route::get('{farePolicy}/edit', 'edit')->whereNumber('farePolicy')->name('edit')->can('update', 'farePolicy');
    Route::put('{farePolicy}', 'update')->whereNumber('farePolicy')->name('update')->can('update', 'farePolicy');
    Route::post('{farePolicy}/activate', 'activate')->whereNumber('farePolicy')->name('activate')->can('activate', 'farePolicy');
    Route::post('{farePolicy}/deactivate', 'deactivate')->whereNumber('farePolicy')->name('deactivate')->can('deactivate', 'farePolicy');
    Route::delete('{farePolicy}', 'destroy')->whereNumber('farePolicy')->name('destroy')->can('delete', 'farePolicy');
});
