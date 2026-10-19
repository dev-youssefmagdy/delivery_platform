<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LookupController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel (ADR-01). Registered in bootstrap/app.php with prefix config('admin.path') (env ADMIN_PATH,
| default "admin"), name `admin.` and middleware `web` + HandlePrecognitiveRequests; the `admin` guard is the
| default guard for the whole prefix (UseAdminGuard).
|
| Public pages (login, MFA challenge, password reset, logout, locale) live in routes/admin/auth.php. Everything
| else requires an authenticated, active admin with a valid tracked session and (where required) enrolled MFA.
| Each route is then guarded by `can:` (policy) middleware, or explicitly marked `admin.access:self` (own
| account) – the authorization guard test fails for any admin route with neither.
|
| Module routes live in routes/admin/*.php (one file per module, written by make:admin-module).
*/

require __DIR__.'/admin/auth.php';

Route::middleware(['auth:admin', 'admin.session', 'admin.mfa'])->group(function (): void {
    Route::get('/', HomeController::class)->name('home')->middleware('admin.access:self');
    Route::get('dashboard', DashboardController::class)->name('dashboard')->can('dashboard.view');
    // Lookups check their own permission per lookup; exports are owner-scoped in the service.
    Route::get('lookups/{lookup}', LookupController::class)->where('lookup', '[a-z0-9_-]+')->name('lookups')->middleware('admin.access:self');
    Route::get('exports/{export}', [ExportController::class, 'show'])->name('exports.show')->middleware('admin.access:self');
    Route::get('exports/{export}/download', [ExportController::class, 'download'])->middleware(['signed', 'admin.access:self'])->name('exports.download');

    foreach (glob(__DIR__.'/admin/*.php') ?: [] as $moduleRoutes) {
        if (basename($moduleRoutes) !== 'auth.php') {
            require $moduleRoutes;
        }
    }
});
