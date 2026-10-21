<?php

use App\Http\Controllers\Admin\Finance\JournalEntryController;
use App\Http\Controllers\Admin\Finance\LedgerAccountController;
use App\Http\Controllers\Admin\Finance\ReconciliationController;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\LedgerReconciliationRun;
use Illuminate\Support\Facades\Route;

/*
| Ledger (ledger.view) and its reconciliation report (finance.view, finance.reconcile to run). Everything here is
| read-only: nothing in the panel can edit or delete an account balance, an entry or a line. Loaded by routes/admin.php.
*/
Route::prefix('ledger-accounts')->name('ledger-accounts.')->controller(LedgerAccountController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', LedgerAccount::class);
    Route::get('{ledgerAccount}', 'show')->whereUuid('ledgerAccount')->name('show')->can('view', 'ledgerAccount');
    Route::get('{ledgerAccount}/statement', 'statement')->whereUuid('ledgerAccount')->name('statement')->can('view', 'ledgerAccount');
});

Route::prefix('journal-entries')->name('journal-entries.')->controller(JournalEntryController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', JournalEntry::class);
    Route::get('{journalEntry}', 'show')->whereUuid('journalEntry')->name('show')->can('view', 'journalEntry');
});

Route::prefix('ledger-reconciliation')->name('ledger-reconciliation.')->controller(ReconciliationController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', LedgerReconciliationRun::class);
    Route::post('run', 'run')->name('run')->can('run', LedgerReconciliationRun::class);
    Route::get('{ledgerReconciliation}', 'show')->whereUuid('ledgerReconciliation')->name('show')->can('view', 'ledgerReconciliation');
});
