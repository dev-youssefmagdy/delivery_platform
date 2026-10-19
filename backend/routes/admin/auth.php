<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\MfaChallengeController;
use App\Http\Controllers\Admin\Auth\PasswordResetController;
use App\Http\Controllers\Admin\LocaleController;
use Illuminate\Support\Facades\Route;

/*
| Admin authentication routes (ADR-06), reachable without a session. Loaded by routes/admin.php.
| Failed login attempts are throttled (and recorded) by AdminAuthService per email + IP; Precognition validation
| calls are never counted. The other public POST routes use the named `admin-login` limiter, which ignores them too.
*/
Route::middleware('admin.access:public')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');

        Route::get('mfa/challenge', [MfaChallengeController::class, 'create'])->name('mfa.challenge');
        Route::post('mfa/challenge', [MfaChallengeController::class, 'store'])->name('mfa.verify');

        Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:admin-login')->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:admin-login')->name('password.update');
    });

    Route::post('logout', LogoutController::class)->name('logout');
    Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');
});
