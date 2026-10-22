<?php

use App\Http\Controllers\Admin\Booking\CancellationPolicyController;
use App\Models\CancellationPolicy;
use Illuminate\Support\Facades\Route;

/*
| Cancellation policies (cancellation_policies.manage): free grace and waiting time, fees per actor and stage, allowed
| reasons. Loaded by routes/admin.php.
*/
Route::prefix('cancellation-policies')->name('cancellation-policies.')->controller(CancellationPolicyController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', CancellationPolicy::class);
    Route::get('create', 'create')->name('create')->can('create', CancellationPolicy::class);
    Route::post('/', 'store')->name('store')->can('create', CancellationPolicy::class);
    Route::get('{cancellationPolicy}', 'show')->whereNumber('cancellationPolicy')->name('show')->can('view', 'cancellationPolicy');
    Route::get('{cancellationPolicy}/activity', 'activity')->whereNumber('cancellationPolicy')->name('activity')->can('view', 'cancellationPolicy');
    Route::get('{cancellationPolicy}/edit', 'edit')->whereNumber('cancellationPolicy')->name('edit')->can('update', 'cancellationPolicy');
    Route::put('{cancellationPolicy}', 'update')->whereNumber('cancellationPolicy')->name('update')->can('update', 'cancellationPolicy');
    Route::post('{cancellationPolicy}/preview', 'preview')->whereNumber('cancellationPolicy')->name('preview')->can('view', 'cancellationPolicy');
    Route::post('{cancellationPolicy}/activate', 'activate')->whereNumber('cancellationPolicy')->name('activate')->can('activate', 'cancellationPolicy');
    Route::post('{cancellationPolicy}/deactivate', 'deactivate')->whereNumber('cancellationPolicy')->name('deactivate')->can('deactivate', 'cancellationPolicy');
    Route::delete('{cancellationPolicy}', 'destroy')->whereNumber('cancellationPolicy')->name('destroy')->can('delete', 'cancellationPolicy');
});
