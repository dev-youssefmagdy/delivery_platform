<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\LookupController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel (ADR-01). Registered in bootstrap/app.php with prefix config('admin.path') (env ADMIN_PATH,
| default "admin"), name `admin.` and middleware `web` + HandlePrecognitiveRequests (+ the temporary
| AllowLocalGuestAccess until admin authentication lands in P1.1).
|
| Module routes live in routes/admin/*.php (one file per module, written by make:admin-module).
*/

Route::get('/', HomeController::class)->name('home');
Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');
Route::get('lookups/{lookup}', LookupController::class)->where('lookup', '[a-z0-9_-]+')->name('lookups');
Route::get('exports/{export}', [ExportController::class, 'show'])->name('exports.show');
Route::get('exports/{export}/download', [ExportController::class, 'download'])->middleware('signed')->name('exports.download');

foreach (glob(__DIR__.'/admin/*.php') ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}
