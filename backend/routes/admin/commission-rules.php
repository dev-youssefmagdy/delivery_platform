<?php

use App\Http\Controllers\Admin\Finance\CommissionRuleController;
use App\Models\CommissionRule;
use Illuminate\Support\Facades\Route;

/*
| Commission rules (commission_rules.manage): the platform's share by service, zone, provider tier and campaign, with
| immutable versions and a read-only calculator. Rules are disabled, never deleted. Loaded by routes/admin.php.
*/
Route::prefix('commission-rules')->name('commission-rules.')->controller(CommissionRuleController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', CommissionRule::class);
    Route::get('create', 'create')->name('create')->can('create', CommissionRule::class);
    Route::post('/', 'store')->name('store')->can('create', CommissionRule::class);
    Route::get('{commissionRule}', 'show')->whereNumber('commissionRule')->name('show')->can('view', 'commissionRule');
    Route::get('{commissionRule}/activity', 'activity')->whereNumber('commissionRule')->name('activity')->can('view', 'commissionRule');
    Route::post('{commissionRule}/preview', 'preview')->whereNumber('commissionRule')->name('preview')->can('view', 'commissionRule');
    Route::get('{commissionRule}/edit', 'edit')->whereNumber('commissionRule')->name('edit')->can('update', 'commissionRule');
    Route::put('{commissionRule}', 'update')->whereNumber('commissionRule')->name('update')->can('update', 'commissionRule');
    Route::post('{commissionRule}/activate', 'activate')->whereNumber('commissionRule')->name('activate')->can('activate', 'commissionRule');
    Route::post('{commissionRule}/deactivate', 'deactivate')->whereNumber('commissionRule')->name('deactivate')->can('deactivate', 'commissionRule');
});
