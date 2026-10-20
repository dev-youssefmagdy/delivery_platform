<?php

use App\Http\Controllers\Admin\Provider\ProviderFileController;
use App\Http\Controllers\Admin\Provider\VehicleController;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Route;

/*
| Vehicles (vehicles.view / approve / reject). Loaded by routes/admin.php. Vehicles are addressed by uuid; providers
| create them in the driver app, so there is no create, edit or delete route.
*/
Route::prefix('vehicles')->name('vehicles.')->controller(VehicleController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Vehicle::class);
    Route::get('{vehicle:uuid}', 'show')->whereUuid('vehicle')->name('show')->can('view', 'vehicle');
    Route::get('{vehicle:uuid}/tabs/documents', 'documents')->whereUuid('vehicle')->name('tabs.documents')->can('view', 'vehicle');
    Route::get('{vehicle:uuid}/tabs/audit', 'audit')->whereUuid('vehicle')->name('tabs.audit')->can('view', 'vehicle');
    Route::post('{vehicle:uuid}/approve', 'approve')->whereUuid('vehicle')->name('approve')->can('approve', 'vehicle');
    Route::post('{vehicle:uuid}/reject', 'reject')->whereUuid('vehicle')->name('reject')->can('reject', 'vehicle');
});

// Short-lived URL for a vehicle photo (the photo itself is served by the signed provider-files route).
Route::post('vehicle-photos/{photo:uuid}/url', [ProviderFileController::class, 'vehiclePhotoUrl'])->whereUuid('photo')->name('vehicle-photos.url')->can('viewAny', Vehicle::class);
