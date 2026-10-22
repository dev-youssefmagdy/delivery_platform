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
use App\Http\Controllers\Api\V1\Customer\BookingAdjustmentController;
use App\Http\Controllers\Api\V1\Customer\BookingController;
use App\Http\Controllers\Api\V1\Customer\BookingDeliveryController;
use App\Http\Controllers\Api\V1\Provider\DeliveryController;
use App\Http\Controllers\Api\V1\Customer\BookingReceiptController;
use App\Http\Controllers\Api\V1\Provider\TripAdjustmentController;
use App\Http\Controllers\Api\V1\Provider\TripReceiptController;
use App\Http\Controllers\Api\V1\Customer\CouponController;
use App\Http\Controllers\Api\V1\Customer\BookingCancellationController;
use App\Http\Controllers\Api\V1\Customer\BookingFeedbackController;
use App\Http\Controllers\Api\V1\Customer\BookingOfferController;
use App\Http\Controllers\Api\V1\Customer\BookingShareController;
use App\Http\Controllers\Api\V1\Customer\PaymentController;
use App\Http\Controllers\Api\V1\Geo\CityController;
use App\Http\Controllers\Api\V1\Geo\PlaceController;
use App\Http\Controllers\Api\V1\Geo\RouteController;
use App\Http\Controllers\Api\V1\Geo\ServiceAreaController;
use App\Http\Controllers\Api\V1\Me\AccountDeletionController;
use App\Http\Controllers\Api\V1\Me\ConsentController;
use App\Http\Controllers\Api\V1\Me\DeviceController;
use App\Http\Controllers\Api\V1\Me\MeController;
use App\Http\Controllers\Api\V1\Me\MessageController;
use App\Http\Controllers\Api\V1\Me\NotificationController;
use App\Http\Controllers\Api\V1\Me\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\Me\PaymentMethodController;
use App\Http\Controllers\Api\V1\Me\WalletController;
use App\Http\Controllers\Api\V1\Me\PhotoController;
use App\Http\Controllers\Api\V1\Me\SavedPlaceController;
use App\Http\Controllers\Api\V1\Provider\ApplicationController;
use App\Http\Controllers\Api\V1\Provider\AvailabilityController;
use App\Http\Controllers\Api\V1\Provider\CashCollectionController;
use App\Http\Controllers\Api\V1\Provider\DocumentController;
use App\Http\Controllers\Api\V1\Provider\EarningsController;
use App\Http\Controllers\Api\V1\Provider\EligibilityController;
use App\Http\Controllers\Api\V1\Provider\JobHistoryController;
use App\Http\Controllers\Api\V1\Provider\LocationController;
use App\Http\Controllers\Api\V1\Provider\OfferController;
use App\Http\Controllers\Api\V1\Provider\PayoutController;
use App\Http\Controllers\Api\V1\Provider\PayoutMethodController;
use App\Http\Controllers\Api\V1\Provider\TripController;
use App\Http\Controllers\Api\V1\Provider\TripFeedbackController;
use App\Http\Controllers\Api\V1\Provider\RequestController;
use App\Http\Controllers\Api\V1\Provider\VehicleController;
use App\Http\Controllers\Api\V1\Support\BookingChatController;
use App\Http\Controllers\Api\V1\Support\SupportTicketController;
use App\Http\Controllers\Api\V1\Webhooks\PaymentWebhookController;
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
    // The in-app inbox and notification settings (P3.6): status-safe, for the customer and the driver app alike.
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('notifications.read');
    Route::get('notification-preferences', [NotificationPreferenceController::class, 'show'])->name('notification-preferences.show');
    Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
    Route::middleware('ability:customer')->group(function (): void {
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('messages/{message}/read', [MessageController::class, 'read'])->whereUuid('message')->name('messages.read');
        // Saved online payment methods (P3.2): only brand and last four digits are returned.
        Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        // The customer wallet (P3.4): balance and statement, read-only and scoped to the owner.
        Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::get('wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
        // Settle the wallet debt (unpaid cancellation fees) through the payment flow (P3.8, ADR-143).
        Route::post('wallet/settle-debt', [WalletController::class, 'settleDebt'])->middleware('idempotent')->name('wallet.settle-debt');
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
            Route::post('payment-methods/setup-intent', [PaymentMethodController::class, 'setupIntent'])->middleware('idempotent')->name('payment-methods.setup-intent');
            Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->whereUuid('paymentMethod')->middleware('idempotent')->name('payment-methods.destroy');
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

    // The trip after selection (P2.5). Cancelling, stopping a share and reporting never need a clean account standing: they
    // reduce harm. `preview=true` on cancel only computes the fee and needs no Idempotency-Key.
    Route::post('{booking}/cancel', [BookingCancellationController::class, 'cancel'])->whereUuid('booking')->middleware('idempotent:unless_preview')->name('cancel');
    Route::post('{booking}/share', [BookingShareController::class, 'store'])->whereUuid('booking')->name('share.store');
    Route::delete('{booking}/share', [BookingShareController::class, 'destroy'])->whereUuid('booking')->name('share.destroy');
    Route::post('{booking}/rating', [BookingFeedbackController::class, 'rate'])->whereUuid('booking')->middleware('idempotent')->name('rating.store');
    Route::post('{booking}/reports', [BookingFeedbackController::class, 'report'])->whereUuid('booking')->middleware('idempotent')->name('reports.store');
    // Online payment (P3.2): a card / wallet booking is paid after completion; a tip is a separate, optional charge. Neither
    // can mark anything paid: only the gateway's verified webhook (or a server-side query) does.
    Route::post('{booking}/payment-intents', [PaymentController::class, 'intent'])->whereUuid('booking')->middleware('idempotent')->name('payment-intents.store');
    Route::post('{booking}/tips', [PaymentController::class, 'tip'])->whereUuid('booking')->middleware('idempotent')->name('tips.store');
    // Fare adjustments after selection (P3.5, SRS §9.4): the other party approves in the app; only approved ones enter the price.
    Route::get('{booking}/adjustments', [BookingAdjustmentController::class, 'index'])->whereUuid('booking')->name('adjustments.index');
    Route::post('{booking}/adjustments', [BookingAdjustmentController::class, 'store'])->whereUuid('booking')->middleware('idempotent')->name('adjustments.store');
    Route::post('{booking}/adjustments/{adjustment}/approve', [BookingAdjustmentController::class, 'approve'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.approve');
    Route::post('{booking}/adjustments/{adjustment}/reject', [BookingAdjustmentController::class, 'reject'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.reject');
    Route::post('{booking}/adjustments/{adjustment}/withdraw', [BookingAdjustmentController::class, 'withdraw'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.withdraw');
    // The immutable receipt of a completed booking (JSON + a signed link to the printable HTML receipt).
    Route::get('{booking}/receipt', [BookingReceiptController::class, 'show'])->whereUuid('booking')->name('receipt');
    // Booking chat (P3.7, SRS §14.2): only the customer and the selected provider, inside the configured window. Reading and the
    // read receipt stay possible for a participant; writing needs an open window and an account in good standing.
    Route::get('{booking}/messages', [BookingChatController::class, 'index'])->whereUuid('booking')->name('messages.index');
    Route::post('{booking}/messages/read', [BookingChatController::class, 'read'])->whereUuid('booking')->name('messages.read');
    Route::get('{booking}/messages/{message}/attachment', [BookingChatController::class, 'attachment'])->whereUuid(['booking', 'message'])->middleware('signed')->name('messages.attachment');
    Route::post('{booking}/messages', [BookingChatController::class, 'store'])->whereUuid('booking')->middleware(['account.active', 'throttle:'.(int) config('support.chat.per_minute', 30).',1', 'idempotent'])->name('messages.store');
    // Parcel proofs and the decision after a failed delivery attempt (P4.1, SRS §7.5, §20.4). Pictures only via the signed route.
    Route::get('{booking}/proofs', [BookingDeliveryController::class, 'proofs'])->whereUuid('booking')->name('proofs.index');
    Route::get('{booking}/proofs/{proof}/file', [BookingDeliveryController::class, 'proofFile'])->whereUuid(['booking', 'proof'])->middleware('signed')->name('proofs.file');
    Route::post('{booking}/delivery-attempts/{attempt}/resolve', [BookingDeliveryController::class, 'resolveAttempt'])->whereUuid(['booking', 'attempt'])->middleware('idempotent')->name('delivery-attempts.resolve');
    // A disputed cancellation fee opens a support ticket carrying the fee decision snapshot.
    Route::post('{booking}/cancellation-appeal', [SupportTicketController::class, 'appeal'])->whereUuid('booking')->middleware('idempotent')->name('cancellation-appeal');

    Route::middleware('account.active:strict')->group(function (): void {
        Route::post('/', [BookingController::class, 'store'])->middleware('idempotent')->name('store');
        Route::patch('{booking}/request', [BookingController::class, 'updateRequest'])->whereUuid('booking')->middleware('idempotent')->name('request.update');

        // Choosing a provider (P2.4): atomic, idempotent; a lost race answers 409 with the fresh booking.
        Route::post('{booking}/offers/{offer}/accept', [BookingOfferController::class, 'accept'])->whereUuid(['booking', 'offer'])->middleware('idempotent')->name('offers.accept');
        Route::post('{booking}/offers/{offer}/reject', [BookingOfferController::class, 'reject'])->whereUuid(['booking', 'offer'])->middleware('idempotent')->name('offers.reject');
    });
});

// Coupons (P3.3, SRS §20.5): a quote for a prospective booking (limited per user) and the customer's own coupon overview. The
// reservation itself happens at booking creation (`coupon_code` on POST /bookings); a client discount is never read.
Route::middleware(['auth:sanctum', 'ability:customer', 'user.seen'])->group(function (): void {
    Route::post('coupons/validate', [CouponController::class, 'validateCoupon'])->middleware('throttle:30,1')->name('coupons.validate');
    Route::get('me/coupons', [CouponController::class, 'index'])->name('me.coupons.index');
});

// Re-check one of the customer's own payments with the gateway after returning from the payment page (P3.2). Read-only for the
// platform's state until the gateway itself says paid; limited per user.
Route::middleware(['auth:sanctum', 'ability:customer', 'user.seen', 'throttle:30,1'])->post('payments/{payment}/refresh', [PaymentController::class, 'refresh'])
    ->whereUuid('payment')->name('payments.refresh');

// Payment gateway callbacks (P3.2): no user authentication, the signature is checked over the raw body by the driver; own rate limit.
Route::post('payment-gateways/{gateway}/webhook', PaymentWebhookController::class)
    ->whereIn('gateway', App\Enums\Payments\PaymentGatewayName::values())
    ->middleware(RateLimiterName::PaymentWebhooks->middleware())
    ->withoutMiddleware('throttle:api')
    ->name('payment-gateways.webhook');

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
    // Job history (P2.8): the driver's own bookings, newest first, paginated.
    Route::get('bookings', [JobHistoryController::class, 'index'])->name('bookings.index');
    Route::get('bookings/active', [TripController::class, 'active'])->name('bookings.active');
    Route::get('bookings/{booking}', [RequestController::class, 'show'])->whereUuid('booking')->name('bookings.show');
    Route::get('bookings/{booking}/adjustments', [TripAdjustmentController::class, 'index'])->whereUuid('booking')->name('adjustments.index');
    Route::get('bookings/{booking}/receipt', [TripReceiptController::class, 'show'])->whereUuid('booking')->name('receipt');
    // Proofs of the provider's own job (P4.1); pictures only via the signed route.
    Route::get('bookings/{booking}/proofs', [DeliveryController::class, 'proofs'])->whereUuid('booking')->name('bookings.proofs.index');
    Route::get('bookings/{booking}/proofs/{proof}/file', [DeliveryController::class, 'proofFile'])->whereUuid(['booking', 'proof'])->middleware('signed')->name('bookings.proofs.file');
    // Booking chat with the customer (P3.7): same rules as the customer side, decided by the service.
    Route::get('bookings/{booking}/messages', [BookingChatController::class, 'index'])->whereUuid('booking')->name('bookings.messages.index');
    Route::post('bookings/{booking}/messages/read', [BookingChatController::class, 'read'])->whereUuid('booking')->name('bookings.messages.read');
    Route::get('bookings/{booking}/messages/{message}/attachment', [BookingChatController::class, 'attachment'])->whereUuid(['booking', 'message'])->middleware('signed')->name('bookings.messages.attachment');
    Route::post('bookings/{booking}/cancellation-appeal', [SupportTicketController::class, 'appeal'])->whereUuid('booking')->middleware('idempotent')->name('bookings.cancellation-appeal');
    // Earnings, wallet statement and payouts (P3.4, SRS §7.6): reads are status-safe, always scoped to the signed-in provider.
    Route::get('earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');
    Route::get('earnings/transactions', [EarningsController::class, 'transactions'])->name('earnings.transactions');
    Route::get('wallet/transactions', [EarningsController::class, 'wallet'])->name('wallet.transactions');
    Route::get('payout-methods', [PayoutMethodController::class, 'index'])->name('payout-methods.index');
    Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [PayoutController::class, 'show'])->whereUuid('payout')->name('payouts.show');
    Route::get('vehicle-photos/{photo}/file', [VehicleController::class, 'photoFile'])->whereUuid('photo')->middleware('signed')->name('vehicle-photos.file');

    Route::middleware('account.active')->group(function (): void {
        // Availability and live location (P2.2). Going offline has no idempotency key: it is naturally idempotent.
        Route::post('availability/online', [AvailabilityController::class, 'online'])->name('availability.online');
        Route::post('availability/offline', [AvailabilityController::class, 'offline'])->name('availability.offline');
        Route::post('location', [LocationController::class, 'store'])->middleware(RateLimiterName::Location->middleware())->name('location.store');

        Route::post('bookings/{booking}/offers/accept-customer-fare', [OfferController::class, 'acceptCustomerFare'])->whereUuid('booking')->middleware([RateLimiterName::Offers->middleware(), 'idempotent'])->name('offers.accept-customer-fare');
        Route::post('bookings/{booking}/offers', [OfferController::class, 'store'])->whereUuid('booking')->middleware([RateLimiterName::Offers->middleware(), 'idempotent'])->name('offers.store');
        Route::post('offers/{offer}/withdraw', [OfferController::class, 'withdraw'])->whereUuid('offer')->name('offers.withdraw');
        // Trip lifecycle (P2.5): every write is idempotent and ownership-checked; transitions go through BookingTransitionService.
        Route::post('bookings/{booking}/acknowledge', [TripController::class, 'acknowledge'])->whereUuid('booking')->middleware('idempotent')->name('trip.acknowledge');
        Route::post('bookings/{booking}/arrived', [TripController::class, 'arrived'])->whereUuid('booking')->middleware('idempotent')->name('trip.arrived');
        Route::post('bookings/{booking}/verify-start-pin', [TripController::class, 'verifyStartPin'])->whereUuid('booking')->middleware('idempotent')->name('trip.verify-start-pin');
        Route::post('bookings/{booking}/start', [TripController::class, 'start'])->whereUuid('booking')->middleware('idempotent')->name('trip.start');
        // Parcel jobs (P4.1): proofs, custody steps, the recipient code, failed attempts and what happens after them.
        Route::post('bookings/{booking}/proofs', [DeliveryController::class, 'storeProof'])->whereUuid('booking')->middleware(['throttle:'.(int) config('courier.proofs.per_minute', 20).',1', 'idempotent'])->name('bookings.proofs.store');
        Route::post('bookings/{booking}/custody', [DeliveryController::class, 'custody'])->whereUuid('booking')->middleware('idempotent')->name('bookings.custody');
        Route::post('bookings/{booking}/verify-recipient-otp', [DeliveryController::class, 'verifyRecipientOtp'])->whereUuid('booking')->middleware(['throttle:'.(int) config('courier.proofs.per_minute', 20).',1', 'idempotent'])->name('bookings.recipient-otp.verify');
        Route::post('bookings/{booking}/recipient-otp/resend', [DeliveryController::class, 'resendRecipientOtp'])->whereUuid('booking')->middleware('idempotent')->name('bookings.recipient-otp.resend');
        Route::post('bookings/{booking}/delivery-attempts', [DeliveryController::class, 'storeAttempt'])->whereUuid('booking')->middleware(['throttle:'.(int) config('courier.proofs.per_minute', 20).',1', 'idempotent'])->name('bookings.delivery-attempts.store');
        Route::post('bookings/{booking}/delivery-attempts/{attempt}/resolve', [DeliveryController::class, 'resolveAttempt'])->whereUuid(['booking', 'attempt'])->middleware('idempotent')->name('bookings.delivery-attempts.resolve');
        Route::post('bookings/{booking}/complete', [TripController::class, 'complete'])->whereUuid('booking')->middleware('idempotent')->name('trip.complete');
        // The provider confirms the cash was collected (P3.2): cash_due -> cash_collected.
        Route::post('bookings/{booking}/cash-collected', [CashCollectionController::class, 'collected'])->whereUuid('booking')->middleware('idempotent')->name('trip.cash-collected');
        Route::post('bookings/{booking}/cancel', [TripController::class, 'cancel'])->whereUuid('booking')->middleware('idempotent:unless_preview')->name('trip.cancel');
        Route::post('bookings/{booking}/rating', [TripFeedbackController::class, 'rate'])->whereUuid('booking')->middleware('idempotent')->name('trip.rating');
        Route::post('bookings/{booking}/reports', [TripFeedbackController::class, 'report'])->whereUuid('booking')->middleware('idempotent')->name('trip.reports');
        // Fare adjustments (P3.5): the provider asks for an extra stop / waiting / toll at cost / scope change, or answers the customer's request.
        Route::post('bookings/{booking}/adjustments', [TripAdjustmentController::class, 'store'])->whereUuid('booking')->middleware('idempotent')->name('adjustments.store');
        Route::post('bookings/{booking}/adjustments/{adjustment}/approve', [TripAdjustmentController::class, 'approve'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.approve');
        Route::post('bookings/{booking}/adjustments/{adjustment}/reject', [TripAdjustmentController::class, 'reject'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.reject');
        Route::post('bookings/{booking}/adjustments/{adjustment}/withdraw', [TripAdjustmentController::class, 'withdraw'])->whereUuid(['booking', 'adjustment'])->middleware('idempotent')->name('adjustments.withdraw');
        Route::post('bookings/{booking}/skip', [RequestController::class, 'skip'])->whereUuid('booking')->name('requests.skip');
        Route::post('bookings/{booking}/messages', [BookingChatController::class, 'store'])->whereUuid('booking')->middleware(['throttle:'.(int) config('support.chat.per_minute', 30).',1', 'idempotent'])->name('bookings.messages.store');

        Route::post('applications', [ApplicationController::class, 'store'])->middleware('idempotent')->name('applications.store');
        Route::patch('application', [ApplicationController::class, 'update'])->name('application.update');
        Route::post('application/submit', [ApplicationController::class, 'submit'])->middleware('idempotent')->name('application.submit');

        // Payout methods and payouts (P3.4): a payout reserves the balance in the ledger in one transaction, needs a verified method
        // and an Idempotency-Key.
        Route::post('payout-methods', [PayoutMethodController::class, 'store'])->middleware('idempotent')->name('payout-methods.store');
        Route::patch('payout-methods/{method}', [PayoutMethodController::class, 'update'])->whereUuid('method')->name('payout-methods.update');
        Route::post('payout-methods/{method}/default', [PayoutMethodController::class, 'makeDefault'])->whereUuid('method')->name('payout-methods.default');
        Route::delete('payout-methods/{method}', [PayoutMethodController::class, 'destroy'])->whereUuid('method')->name('payout-methods.destroy');
        Route::post('payouts', [PayoutController::class, 'store'])->middleware('idempotent')->name('payouts.store');
        Route::post('payouts/{payout}/cancel', [PayoutController::class, 'cancel'])->whereUuid('payout')->middleware('idempotent')->name('payouts.cancel');

        Route::post('documents', [DocumentController::class, 'store'])->middleware([RateLimiterName::ProviderUploads->middleware(), 'idempotent'])->name('documents.store');

        Route::post('vehicles', [VehicleController::class, 'store'])->middleware('idempotent')->name('vehicles.store');
        Route::patch('vehicles/{vehicle:uuid}', [VehicleController::class, 'update'])->whereUuid('vehicle')->name('vehicles.update');
        Route::delete('vehicles/{vehicle:uuid}', [VehicleController::class, 'destroy'])->whereUuid('vehicle')->name('vehicles.destroy');
        Route::post('vehicles/{vehicle:uuid}/photos', [VehicleController::class, 'storePhoto'])->whereUuid('vehicle')->middleware([RateLimiterName::ProviderUploads->middleware(), 'idempotent'])->name('vehicles.photos.store');
        Route::delete('vehicles/{vehicle:uuid}/photos/{photo}', [VehicleController::class, 'destroyPhoto'])->whereUuid(['vehicle', 'photo'])->name('vehicles.photos.destroy');
        Route::post('vehicles/{vehicle:uuid}/submit-review', [VehicleController::class, 'submitReview'])->whereUuid('vehicle')->middleware('idempotent')->name('vehicles.submit-review');
    });
});

// Help center and tickets (P3.7, SRS §14.3) for both apps. Reading and writing stay available to a restricted account (asking
// support must work when something is wrong with the account); everything is scoped to the signed-in person and app.
Route::middleware(['auth:sanctum', 'ability:customer,provider', 'user.seen'])->prefix('support')->name('support.')->group(function (): void {
    Route::get('categories', [SupportTicketController::class, 'categories'])->name('categories');
    Route::get('tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::post('tickets', [SupportTicketController::class, 'store'])->middleware('idempotent')->name('tickets.store');
    Route::get('tickets/{ticket}', [SupportTicketController::class, 'show'])->whereUuid('ticket')->name('tickets.show');
    Route::post('tickets/{ticket}/messages', [SupportTicketController::class, 'reply'])->whereUuid('ticket')->middleware('idempotent')->name('tickets.reply');
    Route::get('tickets/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'attachment'])->whereUuid(['ticket', 'attachment'])->middleware('signed')->name('tickets.attachment');
});

// Private channel authorization for the mobile apps (ADR-07): the pusher-protocol clients post `socket_id` and
// `channel_name` here with their bearer token; every channel is decided by `routes/channels.php` through policies.
Route::post('broadcasting/auth', static fn (Request $request) => Broadcast::auth($request))
    ->middleware(['auth:sanctum', 'ability:customer,provider', 'user.seen', 'account.active'])
    ->name('broadcasting.auth');
