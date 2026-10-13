<?php

use App\Http\Controllers\Api\V1\Common\HealthController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 (ADR-01, ADR-14). Registered in bootstrap/app.php with prefix `api/v1`, name `api.v1.` and the
| `api` middleware group (ForceJsonResponse, SetLocale, throttle:api). Critical writes add `idempotent`.
| Mobile auth: `auth:sanctum` + token abilities (P1.3).
*/

Route::get('health', HealthController::class)->name('health');
