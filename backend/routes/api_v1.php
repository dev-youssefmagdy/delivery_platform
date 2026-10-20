<?php

use App\Enums\Common\RateLimiterName;
use App\Enums\Policy\PolicyType;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Common\CatalogController;
use App\Http\Controllers\Api\V1\Common\ConfigController;
use App\Http\Controllers\Api\V1\Common\HealthController;
use App\Http\Controllers\Api\V1\Common\MediaController;
use App\Http\Controllers\Api\V1\Common\PolicyController;
use App\Http\Controllers\Api\V1\Geo\PlaceController;
use App\Http\Controllers\Api\V1\Geo\RouteController;
use App\Http\Controllers\Api\V1\Geo\ServiceAreaController;
use App\Http\Controllers\Api\V1\Me\AccountDeletionController;
use App\Http\Controllers\Api\V1\Me\ConsentController;
use App\Http\Controllers\Api\V1\Me\DeviceController;
use App\Http\Controllers\Api\V1\Me\MeController;
use App\Http\Controllers\Api\V1\Me\PhotoController;
use App\Http\Controllers\Api\V1\Me\SavedPlaceController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 (ADR-01, ADR-14). Registered in bootstrap/app.php with prefix `api/v1`, name `api.v1.` and the
| `api` middleware group (ForceJsonResponse, SetLocale, throttle:api). Critical writes add `idempotent`.
| Mobile auth: `auth:sanctum` + token abilities (P1.3).
*/

Route::get('health', HealthController::class)->name('health');

// Public, cacheable configuration (P1.2): ETag + 30 s max-age.
Route::middleware('cache.headers:public;max_age=30;etag')->group(function (): void {
    Route::get('config/app', [ConfigController::class, 'app'])->name('config.app');
    Route::get('locales', [ConfigController::class, 'locales'])->name('locales');
});

// Service catalog (P1.4): public, only active entries with a published version. With lat/lng the list is filtered
// by the serving zone's availability matrix and opening hours (P1.5).
Route::middleware('cache.headers:public;max_age=30;etag')->group(function (): void {
    Route::get('service-families', [CatalogController::class, 'families'])->name('catalog.families');
    Route::get('service-categories', [CatalogController::class, 'categories'])->name('catalog.categories');
    Route::get('vehicle-categories', [CatalogController::class, 'vehicleCategories'])->name('catalog.vehicle-categories');
});

// Geography (P1.5). Resolving a position is public; anything that spends maps quota needs a signed-in app.
Route::get('service-areas/resolve', [ServiceAreaController::class, 'resolve'])->name('service-areas.resolve');

Route::middleware(['auth:sanctum', 'ability:customer,provider', 'user.seen', 'account.active', 'throttle:60,1'])->group(function (): void {
    Route::get('places/search', [PlaceController::class, 'search'])->name('places.search');
    Route::get('places/reverse', [PlaceController::class, 'reverse'])->name('places.reverse');
    Route::get('routes/estimate', [RouteController::class, 'estimate'])->name('routes.estimate');
});

// Current policy text for the consent screens (P1.3). Only types that have a published version resolve.
Route::get('policies/{type}', [PolicyController::class, 'show'])
    ->whereIn('type', PolicyType::values())
    ->middleware('cache.headers:public;max_age=60;etag')
    ->name('policies.show');

// Private profile photos behind a short-lived signed URL (built by MeResource).
Route::get('media/users/{user}/photo', [MediaController::class, 'userPhoto'])
    ->whereUuid('user')
    ->middleware('signed')
    ->name('media.user-photo');

// Mobile identity (P1.3). A token carries exactly one ability: `customer` or `provider` (ADR-62).
Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('otp/request', [OtpController::class, 'request'])->middleware(RateLimiterName::OtpRequest->middleware())->name('otp.request');
    Route::post('otp/verify', [OtpController::class, 'verify'])->middleware(RateLimiterName::OtpVerify->middleware())->name('otp.verify');

    Route::middleware(['auth:sanctum', 'ability:customer,provider'])->group(function (): void {
        Route::post('refresh', [SessionController::class, 'refresh'])->middleware('account.active')->name('refresh');
        Route::post('logout', [SessionController::class, 'logout'])->name('logout');
    });
});

Route::middleware(['auth:sanctum', 'ability:customer,provider', 'user.seen'])->prefix('me')->name('me.')->group(function (): void {
    // Status-safe: suspended / deletion-pending users must still be able to read their state and leave.
    Route::get('/', [MeController::class, 'show'])->name('show');
    Route::get('consents', [ConsentController::class, 'index'])->name('consents.index');
    Route::delete('account-deletion-requests/{deletionRequest}', [AccountDeletionController::class, 'destroy'])
        ->whereUuid('deletionRequest')->name('account-deletion-requests.destroy');

    Route::middleware('account.active')->group(function (): void {
        Route::patch('/', [MeController::class, 'update'])->name('update');
        Route::post('photo', [PhotoController::class, 'store'])->name('photo.store');
        Route::post('consents', [ConsentController::class, 'store'])->name('consents.store');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');

        // Customer-only (a driver token gets 403).
        Route::middleware('ability:customer')->group(function (): void {
            Route::get('saved-places', [SavedPlaceController::class, 'index'])->name('saved-places.index');
            Route::post('saved-places', [SavedPlaceController::class, 'store'])->name('saved-places.store');
            Route::put('saved-places/{place}', [SavedPlaceController::class, 'update'])->whereUuid('place')->name('saved-places.update');
            Route::delete('saved-places/{place}', [SavedPlaceController::class, 'destroy'])->whereUuid('place')->name('saved-places.destroy');
        });
        Route::post('account-deletion-requests', [AccountDeletionController::class, 'store'])
            ->middleware('idempotent')->name('account-deletion-requests.store');
    });
});
