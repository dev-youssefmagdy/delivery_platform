<?php

use App\Http\Controllers\Admin\Provider\VerificationController;
use App\Models\ProviderProfile;
use Illuminate\Support\Facades\Route;

/*
| Verification queue and review screen (drivers.view to see; drivers.verify to claim, approve and request more
| information; drivers.reject to reject). Loaded by routes/admin.php.
*/
Route::prefix('verification')->name('verification.')->controller(VerificationController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ProviderProfile::class);
    Route::get('{provider:uuid}', 'review')->whereUuid('provider')->name('review')->can('view', 'provider');
    Route::post('{provider:uuid}/claim', 'claim')->whereUuid('provider')->name('claim')->can('claim', 'provider');
    Route::post('{provider:uuid}/approve', 'approve')->whereUuid('provider')->name('approve')->can('verify', 'provider');
    Route::post('{provider:uuid}/request-information', 'requestMoreInformation')->whereUuid('provider')->name('request-information')->can('verify', 'provider');
    Route::post('{provider:uuid}/reject', 'reject')->whereUuid('provider')->name('reject')->can('reject', 'provider');
});
