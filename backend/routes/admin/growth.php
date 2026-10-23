<?php

use App\Http\Controllers\Admin\Growth\IncentiveCampaignController;
use App\Http\Controllers\Admin\Growth\IncentiveProgressController;
use App\Http\Controllers\Admin\Growth\LoyaltyLevelController;
use App\Http\Controllers\Admin\Growth\ReferralCampaignController;
use App\Http\Controllers\Admin\Growth\ReferralRelationshipController;
use App\Http\Controllers\Admin\Growth\ReferralRewardController;
use App\Models\IncentiveCampaign;
use App\Models\LoyaltyLevel;
use App\Models\ReferralCampaign;
use App\Models\ReferralRelationship;
use App\Models\ReferralReward;
use Illuminate\Support\Facades\Route;

/*
| Growth (P5.7): referral campaigns, relationships and rewards, loyalty levels, provider incentive campaigns with quest progress.
| Loaded by routes/admin.php. Campaigns, relationships and progress rows are addressed by uuid, loyalty levels by id.
*/
Route::prefix('referral-campaigns')->name('referral-campaigns.')->controller(ReferralCampaignController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ReferralCampaign::class);
    Route::get('create', 'create')->name('create')->can('create', ReferralCampaign::class);
    Route::post('/', 'store')->name('store')->can('create', ReferralCampaign::class);
    Route::get('{referralCampaign}', 'show')->whereUuid('referralCampaign')->name('show')->can('view', 'referralCampaign');
    Route::get('{referralCampaign}/edit', 'edit')->whereUuid('referralCampaign')->name('edit')->can('update', 'referralCampaign');
    Route::put('{referralCampaign}', 'update')->whereUuid('referralCampaign')->name('update')->can('update', 'referralCampaign');
    Route::post('{referralCampaign}/status', 'transition')->whereUuid('referralCampaign')->name('transition')->can('transition', 'referralCampaign');
    Route::post('{referralCampaign}/budget', 'adjust')->whereUuid('referralCampaign')->name('adjust')->can('adjust', 'referralCampaign');
});

Route::prefix('referral-relationships')->name('referral-relationships.')->controller(ReferralRelationshipController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ReferralRelationship::class);
    Route::get('{referralRelationship}', 'show')->whereUuid('referralRelationship')->name('show')->can('view', 'referralRelationship');
    Route::post('{referralRelationship}/approve', 'approve')->whereUuid('referralRelationship')->name('approve')->can('review', 'referralRelationship');
    Route::post('{referralRelationship}/reject', 'reject')->whereUuid('referralRelationship')->name('reject')->can('review', 'referralRelationship');
});

Route::get('referral-rewards', [ReferralRewardController::class, 'index'])->name('referral-rewards.index')->can('viewAny', ReferralReward::class);

Route::prefix('loyalty-levels')->name('loyalty-levels.')->controller(LoyaltyLevelController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', LoyaltyLevel::class);
    Route::get('create', 'create')->name('create')->can('create', LoyaltyLevel::class);
    Route::post('/', 'store')->name('store')->can('create', LoyaltyLevel::class);
    Route::get('{loyaltyLevel}/edit', 'edit')->whereNumber('loyaltyLevel')->name('edit')->can('update', 'loyaltyLevel');
    Route::put('{loyaltyLevel}', 'update')->whereNumber('loyaltyLevel')->name('update')->can('update', 'loyaltyLevel');
    Route::post('{loyaltyLevel}/toggle', 'toggle')->whereNumber('loyaltyLevel')->name('toggle')->can('toggle', 'loyaltyLevel');
});

Route::prefix('incentive-campaigns')->name('incentive-campaigns.')->controller(IncentiveCampaignController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', IncentiveCampaign::class);
    Route::get('create', 'create')->name('create')->can('create', IncentiveCampaign::class);
    Route::post('/', 'store')->name('store')->can('create', IncentiveCampaign::class);
    Route::get('{incentiveCampaign}', 'show')->whereUuid('incentiveCampaign')->name('show')->can('view', 'incentiveCampaign');
    Route::get('{incentiveCampaign}/edit', 'edit')->whereUuid('incentiveCampaign')->name('edit')->can('update', 'incentiveCampaign');
    Route::put('{incentiveCampaign}', 'update')->whereUuid('incentiveCampaign')->name('update')->can('update', 'incentiveCampaign');
    Route::post('{incentiveCampaign}/status', 'transition')->whereUuid('incentiveCampaign')->name('transition')->can('transition', 'incentiveCampaign');
    Route::post('{incentiveCampaign}/budget', 'adjust')->whereUuid('incentiveCampaign')->name('adjust')->can('adjust', 'incentiveCampaign');
});

Route::prefix('incentive-progress')->name('incentive-progress.')->controller(IncentiveProgressController::class)->group(function (): void {
    Route::post('{incentiveProgress}/release', 'release')->whereUuid('incentiveProgress')->name('release')->can('review', 'incentiveProgress');
    Route::post('{incentiveProgress}/reject', 'reject')->whereUuid('incentiveProgress')->name('reject')->can('review', 'incentiveProgress');
});
