<?php

use App\Http\Controllers\Admin\Policy\PolicyVersionController;
use App\Models\PolicyVersion;
use Illuminate\Support\Facades\Route;

/*
| Policy versions (policies.manage / policies.publish). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('policy-versions')->name('policy-versions.')->controller(PolicyVersionController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PolicyVersion::class);
    Route::get('create', 'create')->name('create')->can('create', PolicyVersion::class);
    Route::post('/', 'store')->name('store')->can('create', PolicyVersion::class);
    Route::get('{policyVersion}', 'show')->whereNumber('policyVersion')->name('show')->can('view', 'policyVersion');
    Route::get('{policyVersion}/activity', 'activity')->whereNumber('policyVersion')->name('activity')->can('view', 'policyVersion');
    Route::get('{policyVersion}/edit', 'edit')->whereNumber('policyVersion')->name('edit')->can('update', 'policyVersion');
    Route::put('{policyVersion}', 'update')->whereNumber('policyVersion')->name('update')->can('update', 'policyVersion');
    Route::post('{policyVersion}/publish', 'publish')->whereNumber('policyVersion')->name('publish')->can('publish', 'policyVersion');
    Route::delete('{policyVersion}', 'destroy')->whereNumber('policyVersion')->name('destroy')->can('delete', 'policyVersion');
});
