<?php

use App\Http\Controllers\Admin\Finance\AdjustmentRequestController;
use App\Http\Controllers\Admin\Finance\CashReconciliationController;
use App\Http\Controllers\Admin\Finance\ProviderStatementController;
use App\Http\Controllers\Admin\Payout\PayoutBatchController;
use App\Http\Controllers\Admin\Payout\PayoutMethodController;
use App\Http\Controllers\Admin\Payout\PayoutRequestController;
use App\Models\CashReconciliation;
use App\Models\FinancialAdjustmentRequest;
use App\Models\LedgerAccount;
use App\Models\PayoutBatch;
use App\Models\PayoutRequest;
use App\Models\ProviderPayoutMethod;
use Illuminate\Support\Facades\Route;

/*
| Provider payouts (payouts.view / payouts.review / payouts.approve), payout batches, payout methods, wallet adjustments
| (wallets.view / wallets.adjust / wallets.approve_adjustment), cash reconciliation (finance.view / finance.reconcile) and the
| provider statement export (finance.view + reports.export). Money moves only through the ledger services; every dangerous step
| needs a reason and is audited. Loaded by routes/admin.php.
*/
Route::prefix('payout-requests')->name('payout-requests.')->controller(PayoutRequestController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PayoutRequest::class);
    Route::get('{payoutRequest}', 'show')->whereUuid('payoutRequest')->name('show')->can('view', 'payoutRequest');
    Route::get('{payoutRequest}/evidence', 'evidence')->whereUuid('payoutRequest')->name('evidence')->can('view', 'payoutRequest');
    Route::post('{payoutRequest}/review', 'startReview')->whereUuid('payoutRequest')->name('review')->can('review', 'payoutRequest');
    Route::post('{payoutRequest}/approve', 'approve')->whereUuid('payoutRequest')->name('approve')->can('approve', 'payoutRequest');
    Route::post('{payoutRequest}/reject', 'reject')->whereUuid('payoutRequest')->name('reject')->can('reject', 'payoutRequest');
    Route::post('{payoutRequest}/processing', 'markProcessing')->whereUuid('payoutRequest')->name('processing')->can('markProcessing', 'payoutRequest');
    Route::post('{payoutRequest}/paid', 'markPaid')->whereUuid('payoutRequest')->name('paid')->can('markPaid', 'payoutRequest');
    Route::post('{payoutRequest}/failed', 'markFailed')->whereUuid('payoutRequest')->name('failed')->can('markFailed', 'payoutRequest');
    Route::post('{payoutRequest}/reverse', 'reverse')->whereUuid('payoutRequest')->name('reverse')->can('reverse', 'payoutRequest');
});

Route::prefix('payout-batches')->name('payout-batches.')->controller(PayoutBatchController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', PayoutBatch::class);
    Route::post('/', 'store')->name('store')->can('createBatch', PayoutRequest::class);
    Route::get('{payoutBatch}', 'show')->whereUuid('payoutBatch')->name('show')->can('view', 'payoutBatch');
    Route::post('{payoutBatch}/export', 'export')->whereUuid('payoutBatch')->name('export')->can('export', 'payoutBatch');
});

Route::prefix('payout-methods')->name('payout-methods.')->controller(PayoutMethodController::class)->group(function (): void {
    Route::post('{payoutMethod}/verify', 'verify')->whereUuid('payoutMethod')->name('verify')->can('verify', 'payoutMethod');
    Route::post('{payoutMethod}/unverify', 'unverify')->whereUuid('payoutMethod')->name('unverify')->can('unverify', 'payoutMethod');
});

Route::prefix('adjustment-requests')->name('adjustment-requests.')->controller(AdjustmentRequestController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', FinancialAdjustmentRequest::class);
    Route::post('/', 'store')->name('store')->can('create', FinancialAdjustmentRequest::class);
    Route::get('{adjustmentRequest}', 'show')->whereUuid('adjustmentRequest')->name('show')->can('view', 'adjustmentRequest');
    Route::post('{adjustmentRequest}/approve', 'approve')->whereUuid('adjustmentRequest')->name('approve')->can('approve', 'adjustmentRequest');
    Route::post('{adjustmentRequest}/reject', 'reject')->whereUuid('adjustmentRequest')->name('reject')->can('reject', 'adjustmentRequest');
    Route::post('{adjustmentRequest}/cancel', 'cancel')->whereUuid('adjustmentRequest')->name('cancel')->can('cancel', 'adjustmentRequest');
});

Route::prefix('cash-reconciliations')->name('cash-reconciliations.')->controller(CashReconciliationController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', CashReconciliation::class);
    Route::post('/', 'store')->name('store')->can('create', CashReconciliation::class);
    Route::get('{cashReconciliation}', 'show')->whereUuid('cashReconciliation')->name('show')->can('view', 'cashReconciliation');
    Route::post('{cashReconciliation}/settle', 'settle')->whereUuid('cashReconciliation')->name('settle')->can('settle', 'cashReconciliation');
    Route::post('{cashReconciliation}/cancel', 'cancel')->whereUuid('cashReconciliation')->name('cancel')->can('cancel', 'cashReconciliation');
});

// Provider statement export (a download of the wallet's ledger lines).
Route::get('providers/{provider:uuid}/statement', [ProviderStatementController::class, 'export'])->whereUuid('provider')->name('providers.statement')
    ->can('view', 'provider')->can('exportStatements', LedgerAccount::class);
