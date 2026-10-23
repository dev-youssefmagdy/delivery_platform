<?php

use App\Http\Controllers\Admin\Lead\LeadController;
use App\Models\Lead;
use Illuminate\Support\Facades\Route;

/*
| Onboarding leads from the public website (leads.view / leads.manage). Loaded by routes/admin.php. Addressed by uuid.
*/
Route::prefix('leads')->name('leads.')->controller(LeadController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Lead::class);
    Route::get('{lead}', 'show')->whereUuid('lead')->name('show')->can('view', 'lead');
    Route::put('{lead}', 'update')->whereUuid('lead')->name('update')->can('update', 'lead');
    Route::post('{lead}/status', 'transition')->whereUuid('lead')->name('transition');
});
