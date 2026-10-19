<?php

use App\Http\Controllers\Admin\Security\MfaController;
use App\Http\Controllers\Admin\Security\SecurityController;
use Illuminate\Support\Facades\Route;

/*
| "My account" (self-service, ADR-06): profile, password, MFA enrolment, recovery codes, own sessions.
| Marked `admin.access:self`: any signed-in admin may act on their own account only; no permission is needed.
| These routes stay reachable while MFA enrolment is still required (EnforceAdminMfa).
*/
Route::prefix('security')->name('security.')->middleware('admin.access:self')->group(function (): void {
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::put('profile', [SecurityController::class, 'updateProfile'])->name('profile.update');
    Route::put('password', [SecurityController::class, 'changePassword'])->name('password.update');

    Route::get('mfa', [MfaController::class, 'show'])->name('mfa');
    Route::post('mfa', [MfaController::class, 'confirm'])->name('mfa.confirm');
    Route::get('mfa/recovery-codes', [MfaController::class, 'recoveryCodes'])->name('mfa.recovery');
    Route::post('mfa/recovery-codes', [MfaController::class, 'regenerate'])->name('mfa.regenerate');
    Route::post('mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');

    Route::delete('sessions', [SecurityController::class, 'revokeOtherSessions'])->name('sessions.destroy-others');
    Route::delete('sessions/{session}', [SecurityController::class, 'revokeSession'])->whereNumber('session')->name('sessions.destroy');
});
