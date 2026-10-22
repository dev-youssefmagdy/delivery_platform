<?php

use App\Http\Controllers\Admin\Notification\PushCampaignController;
use App\Models\PushCampaign;
use Illuminate\Support\Facades\Route;

/*
| Push campaigns (push_campaigns.manage): marketing pushes to opted-in people, sent now or scheduled. Loaded by routes/admin.php.
*/
Route::prefix('push-campaigns')->name('push-campaigns.')->controller(PushCampaignController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PushCampaign::class);
    Route::get('create', 'create')->name('create')->can('create', PushCampaign::class);
    Route::post('/', 'store')->name('store')->can('create', PushCampaign::class);
    Route::get('{pushCampaign}', 'show')->whereUuid('pushCampaign')->name('show')->can('view', 'pushCampaign');
    Route::get('{pushCampaign}/activity', 'activity')->whereUuid('pushCampaign')->name('activity')->can('view', 'pushCampaign');
    Route::get('{pushCampaign}/edit', 'edit')->whereUuid('pushCampaign')->name('edit')->can('update', 'pushCampaign');
    Route::put('{pushCampaign}', 'update')->whereUuid('pushCampaign')->name('update')->can('update', 'pushCampaign');
    Route::post('{pushCampaign}/schedule', 'schedule')->whereUuid('pushCampaign')->name('schedule')->can('schedule', 'pushCampaign');
    Route::post('{pushCampaign}/send', 'send')->whereUuid('pushCampaign')->name('send')->can('send', 'pushCampaign');
    Route::post('{pushCampaign}/cancel', 'cancel')->whereUuid('pushCampaign')->name('cancel')->can('cancel', 'pushCampaign');
});
