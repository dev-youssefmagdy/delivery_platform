<?php

use App\Http\Controllers\Admin\Risk\RiskSignalController;
use App\Models\RiskSignal;
use Illuminate\Support\Facades\Route;

/*
| Risk signals (risk.view / risk.manage). Loaded by routes/admin.php. Signals are internal review tasks addressed by uuid; the four
| actions go through the existing customer / provider account services and each needs a reason.
*/
Route::prefix('risk-signals')->name('risk-signals.')->controller(RiskSignalController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', RiskSignal::class);
    Route::get('{signal}', 'show')->whereUuid('signal')->name('show')->can('view', 'signal');
    Route::post('{signal}/suspend', 'suspend')->whereUuid('signal')->name('suspend')->can('suspend', 'signal');
    Route::post('{signal}/restrict', 'restrict')->whereUuid('signal')->name('restrict')->can('restrict', 'signal');
    Route::post('{signal}/dismiss', 'dismiss')->whereUuid('signal')->name('dismiss')->can('dismiss', 'signal');
    Route::post('{signal}/escalate', 'escalate')->whereUuid('signal')->name('escalate')->can('escalate', 'signal');
});
