<?php

use App\Http\Controllers\Api\V1\Common\ConfigController;
use App\Http\Controllers\Api\V1\Common\HealthController;
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
