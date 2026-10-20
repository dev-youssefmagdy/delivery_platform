<?php

use App\Http\Controllers\Admin\Geography\CoveragePreviewController;
use App\Http\Controllers\Admin\Geography\ServiceZoneController;
use App\Models\ServiceZone;
use Illuminate\Support\Facades\Route;

/*
| Service zones and the coverage preview tool (service_areas.manage). Loaded by routes/admin.php.
*/
Route::prefix('service-zones')->name('service-zones.')->controller(ServiceZoneController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ServiceZone::class);
    Route::get('create', 'create')->name('create')->can('create', ServiceZone::class);
    Route::post('/', 'store')->name('store')->can('create', ServiceZone::class);
    Route::post('overlaps', 'overlaps')->name('overlaps')->can('create', ServiceZone::class);
    Route::get('{serviceZone}', 'show')->whereNumber('serviceZone')->name('show')->can('view', 'serviceZone');
    Route::get('{serviceZone}/activity', 'activity')->whereNumber('serviceZone')->name('activity')->can('view', 'serviceZone');
    Route::get('{serviceZone}/edit', 'edit')->whereNumber('serviceZone')->name('edit')->can('update', 'serviceZone');
    Route::put('{serviceZone}', 'update')->whereNumber('serviceZone')->name('update')->can('update', 'serviceZone');
    Route::put('{serviceZone}/availability', 'availability')->whereNumber('serviceZone')->name('availability')->can('update', 'serviceZone');
    Route::post('{serviceZone}/activate', 'activate')->whereNumber('serviceZone')->name('activate')->can('activate', 'serviceZone');
    Route::post('{serviceZone}/suspend', 'suspend')->whereNumber('serviceZone')->name('suspend')->can('suspend', 'serviceZone');
    Route::delete('{serviceZone}', 'destroy')->whereNumber('serviceZone')->name('destroy')->can('delete', 'serviceZone');
});

Route::prefix('coverage-preview')->name('coverage-preview.')->controller(CoveragePreviewController::class)->group(function (): void {
    Route::get('/', 'show')->name('show')->can('service_areas.manage');
    Route::post('/', 'run')->name('run')->can('service_areas.manage');
});
