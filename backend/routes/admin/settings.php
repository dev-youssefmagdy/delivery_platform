<?php

use App\Enums\Settings\SettingGroup;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Route;

/*
| Platform settings (settings.view / settings.manage). One form per SettingGroup tab. Loaded by routes/admin.php
| inside the authenticated admin group.
*/
Route::prefix('settings')->name('settings.')->controller(SettingsController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PlatformSetting::class);
    Route::put('{group}', 'update')->whereIn('group', SettingGroup::values())->name('update')->can('update', PlatformSetting::class);
});
