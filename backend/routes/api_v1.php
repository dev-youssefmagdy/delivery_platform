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
use App\Http\Controllers\Api\V1\Customer\BookingController;
use App\Http\Controllers\Api\V1\Customer\BookingOfferController;
use App\Http\Controllers\Api\V1\Geo\CityController;
use App\Http\Controllers\Api\V1\Geo\PlaceController;
use App\Http\Controllers\Api\V1\Geo\RouteController;
use App\Http\Controllers\Api\V1\Geo\ServiceAreaController;
use App\Http\Controllers\Api\V1\Me\AccountDeletionController;
use App\Http\Controllers\Api\V1\Me\ConsentController;
use App\Http\Controllers\Api\V1\Me\DeviceController;
use App\Http\Controllers\Api\V1\Me\MeController;
use App\Http\Controllers\Api\V1\Me\MessageController;
use App\Http\Controllers\Api\V1\Me\PhotoController;
use App\Http\Controllers\Api\V1\Me\SavedPlaceController;
use App\Http\Controllers\Api\V1\Provider\ApplicationController;
use App\Http\Controllers\Api\V1\Provider\AvailabilityController;
use App\Http\Controllers\Api\V1\Provider\DocumentController;
use App\Http\Controllers\Api\V1\Provider\EligibilityController;
use App\Http\Controllers\Api\V1\Provider\LocationController;
use App\Http\Controllers\Api\V1\Provider\OfferController;
use App\Http\Controllers\Api\V1\Provider\RequestController;
use App\Http\Controllers\Api\V1\Provider\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
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
Route::get('cities', [CityController::class, 'index'])->middleware('cache.headers:public;max_age=60;etag')->name('cities.index');

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
    Route::middleware('ability:customer')->group(function (): void {
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('messages/{message}/read', [MessageController::class, 'read'])->whereUuid('message')->name('messages.read');
    });
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

// Customer bookings (P2.3, SRS §20.3). `customer` tokens only. Reads are status-safe and always scoped to the owner
// (another customer's booking is a 404). Creating and editing need an active, unrestricted account and an Idempotency-Key.
Route::middleware(['auth:sanctum', 'ability:customer', 'user.seen'])->prefix('bookings')->name('bookings.')->group(function (): void {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('{booking}', [BookingController::class, 'show'])->whereUuid('booking')->name('show');
    Route::get('{booking}/offers', [BookingOfferController::class, 'index'])->whereUuid('booking')->name('offers.index');

    Route::middleware('account.active:strict')->group(function (): void {
        Route::post('/', [BookingController::class, 'store'])->middleware('idempotent')->name('store');
        Route::patch('{booking}/request', [BookingController::class, 'updateRequest'])->whereUuid('booking')->middleware('idempotent')->name('request.update');

        // Choosing a provider (P2.4): atomic, idempotent; a lost race answers 409 with the fresh booking.
        Route::post('{booking}/offers/{offer}/accept', [BookingOfferController::class, 'accept'])->whereUuid(['booking', 'offer'])->middleware('idempotent')->name('offers.accept');
        Route::post('{booking}/offers/{offer}/reject', [BookingOfferController::class, 'reject'])->whereUuid(['booking', 'offer'])->middleware('idempotent')->name('offers.reject');
    });
});

// Provider onboarding (P2.1): application, vehicles, documents, eligibility. `provider` tokens only (a customer token is
// 403). Reads are status-safe so a suspended driver can still see why; writes need an active account. Files are only
// served through the signed routes below (5 minutes) and still require the owner's token.
Route::middleware(['auth:sanctum', 'ability:provider', 'user.seen'])->prefix('provider')->name('provider.')->group(function (): void {
    Route::get('application', [ApplicationController::class, 'show'])->name('application.show');
    Route::get('eligibility', [EligibilityController::class, 'show'])->name('eligibility.show');
    Route::get('availability', [AvailabilityController::class, 'show'])->name('availability.show');
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->whereUuid('document')->name('documents.show');
    Route::get('documents/{document}/file', [DocumentController::class, 'file'])->whereUuid('document')->middleware('signed')->name('documents.file');
    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('vehicles/{vehicle:uuid}', [VehicleController::class, 'show'])->whereUuid('vehicle')->name('vehicles.show');
    // Negotiation (P2.4): masked request cards, own offers, and the full booking only once selected (Plan I10).
    Route::get('requests/available', [RequestController::class, 'available'])->name('requests.available');
    Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('bookings/{booking}', [RequestController::class, 'show'])->whereUuid('booking')->name('bookings.show');
    Route::get('vehicle-photos/{photo}/file', [VehicleController::class, 'photoFile'])->whereUuid('photo')->middleware('signed')->name('vehicle-photos.file');

    Route::middleware('account.active')->group(function (): void {
        // Availability and live location (P2.2). Going offline has no idempotency key: it is naturally idempotent.
        Route::post('availability/online', [AvailabilityController::class, 'online'])->name('availability.online');
        Route::post('availability/offline', [AvailabilityController::class, 'offline'])->name('availability.offline');
        Route::post('location', [LocationController::class, 'store'])->middleware(RateLimiterName::Location->middleware())->name('location.store');

        Route::post('bookings/{booking}/offers/accept-customer-fare', [OfferController::class, 'acceptCustomerFare'])->whereUuid('booking')->middleware([RateLimiterName::Offers->middleware(), 'idempotent'])->name('offers.accept-customer-fare');
        Route::post('bookings/{booking}/offers', [OfferController::class, 'store'])->whereUuid('booking')->middleware([RateLimiterName::Offers->middleware(), 'idempotent'])->name('offers.store');
        Route::post('offers/{offer}/withdraw', [OfferController::class, 'withdraw'])->whereUuid('offer')->name('offers.withdraw');
        Route::post('bookings/{booking}/skip', [RequestController::class, 'skip'])->whereUuid('booking')->name('requests.skip');

        Route::post('applications', [ApplicationController::class, 'store'])->middleware('idempotent')->name('applications.store');
        Route::patch('application', [ApplicationController::class, 'update'])->name('application.update');
        Route::post('application/submit', [ApplicationController::class, 'submit'])->middleware('idempotent')->name('application.submit');

        Route::post('documents', [DocumentController::class, 'store'])->middleware([RateLimiterName::ProviderUploads->middleware(), 'idempotent'])->name('documents.store');

        Route::post('vehicles', [VehicleController::class, 'store'])->middleware('idempotent')->name('vehicles.store');
        Route::patch('vehicles/{vehicle:uuid}', [VehicleController::class, 'update'])->whereUuid('vehicle')->name('vehicles.update');
        Route::delete('vehicles/{vehicle:uuid}', [VehicleController::class, 'destroy'])->whereUuid('vehicle')->name('vehicles.destroy');
        Route::post('vehicles/{vehicle:uuid}/photos', [VehicleController::class, 'storePhoto'])->whereUuid('vehicle')->middleware([RateLimiterName::ProviderUploads->middleware(), 'idempotent'])->name('vehicles.photos.store');
        Route::delete('vehicles/{vehicle:uuid}/photos/{photo}', [VehicleController::class, 'destroyPhoto'])->whereUuid(['vehicle', 'photo'])->name('vehicles.photos.destroy');
        Route::post('vehicles/{vehicle:uuid}/submit-review', [VehicleController::class, 'submitReview'])->whereUuid('vehicle')->middleware('idempotent')->name('vehicles.submit-review');
    });
});

// Private channel authorization for the mobile apps (ADR-07): the pusher-protocol clients post `socket_id` and
// `channel_name` here with their bearer token; every channel is decided by `routes/channels.php` through policies.
Route::post('broadcasting/auth', static fn (Request $request) => Broadcast::auth($request))
    ->middleware(['auth:sanctum', 'ability:customer,provider', 'user.seen', 'account.active'])
    ->name('broadcasting.auth');
