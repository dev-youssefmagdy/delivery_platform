<?php

use App\Enums\Reporting\ReportKey;
use App\Http\Controllers\Admin\Reporting\ReportController;
use Illuminate\Support\Facades\Route;

/*
| Reports (SRS 16.14): `reports.view` plus the permission of the report's own data (ReportPolicy). One page per report (collapsed filters
| card, chart, Yajra table); the CSV export is queued and audited (`reports.export`; personal-data columns need `reports.export_pii`).
| Loaded by routes/admin.php.
*/
Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', ReportKey::class);
    Route::get('{report}', 'show')->whereIn('report', ReportKey::values())->name('show')->can('view', 'report');
    Route::post('{report}/export', 'export')->whereIn('report', ReportKey::values())->name('export')->can('export', 'report');
});
