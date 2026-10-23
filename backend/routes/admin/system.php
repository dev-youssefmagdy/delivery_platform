<?php

use App\Http\Controllers\Admin\System\AppVersionController;
use App\Http\Controllers\Admin\System\FailedJobController;
use App\Http\Controllers\Admin\System\FeatureFlagController;
use App\Http\Controllers\Admin\System\SystemHealthController;
use App\Models\AppVersion;
use App\Models\FailedJob;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Route;

/*
| System operations (P5.6): integration health (system.health), failed queue jobs with audited retry (system.queues),
| app versions (system.app_versions), feature flags (system.feature_flags). Loaded by routes/admin.php.
*/
Route::get('system/health', SystemHealthController::class)->name('system.health')->can('system.health');

Route::prefix('failed-jobs')->name('failed-jobs.')->controller(FailedJobController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', FailedJob::class);
    Route::post('{uuid}/retry', 'retry')->whereUuid('uuid')->name('retry')->can('retry', FailedJob::class);
});

Route::prefix('app-versions')->name('app-versions.')->controller(AppVersionController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', AppVersion::class);
    Route::get('create', 'create')->name('create')->can('create', AppVersion::class);
    Route::post('/', 'store')->name('store')->can('create', AppVersion::class);
    Route::get('{appVersion}/edit', 'edit')->whereNumber('appVersion')->name('edit')->can('update', AppVersion::class);
    Route::put('{appVersion}', 'update')->whereNumber('appVersion')->name('update')->can('update', AppVersion::class);
});

Route::prefix('feature-flags')->name('feature-flags.')->controller(FeatureFlagController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', FeatureFlag::class);
    Route::get('create', 'create')->name('create')->can('create', FeatureFlag::class);
    Route::post('/', 'store')->name('store')->can('create', FeatureFlag::class);
    Route::get('{featureFlag}/edit', 'edit')->whereNumber('featureFlag')->name('edit')->can('update', FeatureFlag::class);
    Route::put('{featureFlag}', 'update')->whereNumber('featureFlag')->name('update')->can('update', FeatureFlag::class);
});
