<?php

use App\Http\Controllers\Admin\Payments\PaymentController;
use App\Http\Controllers\Admin\Payments\PaymentReconciliationController;
use App\Http\Controllers\Admin\Payments\RefundController;
use App\Http\Controllers\Admin\Payments\WebhookEventController;
use App\Models\GatewayWebhookEvent;
use App\Models\Payment;
use App\Models\PaymentReconciliationRun;
use App\Models\PaymentRefund;
use Illuminate\Support\Facades\Route;

/*
| Payments (payments.view), refunds (refunds.request / refunds.approve), gateway webhook events and the payment
| reconciliation report. Payments are read-only in the panel: money moves only through the gateway, the cash rule and the
| refund workflow, every step with a reason and an audit entry. Loaded by routes/admin.php.
*/
Route::prefix('payments')->name('payments.')->controller(PaymentController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Payment::class);
    Route::get('{payment}', 'show')->whereUuid('payment')->name('show')->can('view', 'payment');
    Route::get('{payment}/tabs/refunds', 'refundsTab')->whereUuid('payment')->name('tabs.refunds')->can('view', 'payment');
    Route::get('{payment}/tabs/activity', 'activity')->whereUuid('payment')->name('tabs.activity')->can('view', 'payment');
    Route::post('{payment}/refunds', 'requestRefund')->whereUuid('payment')->name('refunds.store')->can('requestRefund', 'payment');
    Route::post('{payment}/sync', 'sync')->whereUuid('payment')->name('sync')->can('sync', 'payment');
});

// Payment tabs of the booking and customer pages.
Route::get('bookings/{booking:uuid}/tabs/payments', [PaymentController::class, 'bookingTab'])->whereUuid('booking')->name('bookings.tabs.payments')->can('view', 'booking')->can('viewAny', Payment::class);
Route::get('customers/{customer:uuid}/tabs/payments', [PaymentController::class, 'customerTab'])->whereUuid('customer')->withTrashed()->name('customers.tabs.payments')->can('view', 'customer')->can('viewAny', Payment::class);

Route::prefix('payment-refunds')->name('payment-refunds.')->controller(RefundController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PaymentRefund::class);
    Route::get('{paymentRefund}', 'show')->whereUuid('paymentRefund')->name('show')->can('view', 'paymentRefund');
    Route::post('{paymentRefund}/approve', 'approve')->whereUuid('paymentRefund')->name('approve')->can('approve', 'paymentRefund');
    Route::post('{paymentRefund}/reject', 'reject')->whereUuid('paymentRefund')->name('reject')->can('reject', 'paymentRefund');
});

Route::prefix('gateway-webhook-events')->name('gateway-webhook-events.')->controller(WebhookEventController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', GatewayWebhookEvent::class);
    Route::get('{gatewayWebhookEvent}', 'show')->whereUuid('gatewayWebhookEvent')->name('show')->can('view', 'gatewayWebhookEvent');
    Route::post('{gatewayWebhookEvent}/reprocess', 'reprocess')->whereUuid('gatewayWebhookEvent')->name('reprocess')->can('reprocess', 'gatewayWebhookEvent');
});

Route::prefix('payment-reconciliation')->name('payment-reconciliation.')->controller(PaymentReconciliationController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PaymentReconciliationRun::class);
    Route::post('run', 'run')->name('run')->can('run', PaymentReconciliationRun::class);
    Route::get('{paymentReconciliation}', 'show')->whereUuid('paymentReconciliation')->name('show')->can('view', 'paymentReconciliation');
});
