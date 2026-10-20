<?php

use App\Http\Controllers\Admin\Provider\ProviderDocumentController;
use App\Http\Controllers\Admin\Provider\ProviderFileController;
use App\Http\Controllers\Admin\Provider\VehicleDocumentController;
use Illuminate\Support\Facades\Route;

/*
| Provider and vehicle documents: review actions (drivers.verify / drivers.reject; vehicles.approve / vehicles.reject)
| and private file access (documents.view). Files are served only through signed URLs (5 minutes) that are bound to the
| admin they were issued to; every view or download is audited. Loaded by routes/admin.php.
*/
Route::prefix('provider-documents')->name('provider-documents.')->controller(ProviderDocumentController::class)->group(function (): void {
    Route::post('{document:uuid}/url', 'url')->whereUuid('document')->name('url')->can('view', 'document');
    Route::post('{document:uuid}/approve', 'approve')->whereUuid('document')->name('approve')->can('approve', 'document');
    Route::post('{document:uuid}/reject', 'reject')->whereUuid('document')->name('reject')->can('reject', 'document');
});

Route::prefix('vehicle-documents')->name('vehicle-documents.')->controller(VehicleDocumentController::class)->group(function (): void {
    Route::post('{document:uuid}/url', 'url')->whereUuid('document')->name('url')->can('view', 'document');
    Route::post('{document:uuid}/approve', 'approve')->whereUuid('document')->name('approve')->can('approve', 'document');
    Route::post('{document:uuid}/reject', 'reject')->whereUuid('document')->name('reject')->can('reject', 'document');
});

Route::prefix('provider-files')->name('provider-files.')->controller(ProviderFileController::class)->middleware('signed')->group(function (): void {
    Route::get('provider-documents/{document:uuid}', 'providerDocument')->whereUuid('document')->name('provider-document')->can('view', 'document');
    Route::get('vehicle-documents/{document:uuid}', 'vehicleDocument')->whereUuid('document')->name('vehicle-document')->can('view', 'document');
    Route::get('vehicle-photos/{photo:uuid}', 'vehiclePhoto')->whereUuid('photo')->name('vehicle-photo')->can('viewAny', App\Models\Vehicle::class);
});
