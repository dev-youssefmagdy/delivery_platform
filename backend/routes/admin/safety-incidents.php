<?php

use App\Http\Controllers\Admin\Safety\SafetyIncidentController;
use App\Models\SafetyIncident;
use Illuminate\Support\Facades\Route;

/*
| Safety Center (incidents.view / manage / view_evidence / export_restricted). Loaded by routes/admin.php. Cases are addressed by
| uuid. Evidence (narrative, location, notes, files) has its own routes: each load or stream writes an audit entry, and the
| attachment route also needs a signed URL (5 minutes).
*/
Route::prefix('safety-incidents')->name('safety-incidents.')->controller(SafetyIncidentController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', SafetyIncident::class);
    Route::post('export', 'export')->name('export')->can('exportRestricted', SafetyIncident::class);
    Route::get('{incident}', 'show')->whereUuid('incident')->name('show')->can('view', 'incident');
    Route::get('{incident}/activity', 'activity')->whereUuid('incident')->name('activity')->can('view', 'incident');
    Route::get('{incident}/evidence', 'evidence')->whereUuid('incident')->name('evidence')->can('viewEvidence', 'incident');
    Route::get('{incident}/attachments/{attachment}', 'attachment')->whereUuid(['incident', 'attachment'])->name('attachments.show')->middleware('signed')->can('viewEvidence', 'incident');
    Route::get('{incident}/bundle', 'bundle')->whereUuid('incident')->name('bundle')->can('exportEvidence', 'incident');
    Route::post('{incident}/acknowledge', 'acknowledge')->whereUuid('incident')->name('acknowledge')->can('acknowledge', 'incident');
    Route::post('{incident}/assign', 'assign')->whereUuid('incident')->name('assign')->can('assign', 'incident');
    Route::post('{incident}/notes', 'note')->whereUuid('incident')->name('note')->can('note', 'incident');
    Route::post('{incident}/messages', 'message')->whereUuid('incident')->name('message')->can('message', 'incident');
    Route::post('{incident}/triage', 'triage')->whereUuid('incident')->name('triage')->can('triage', 'incident');
    Route::post('{incident}/escalate', 'escalate')->whereUuid('incident')->name('escalate')->can('escalate', 'incident');
    Route::post('{incident}/resolve', 'resolve')->whereUuid('incident')->name('resolve')->can('resolve', 'incident');
    Route::post('{incident}/reopen', 'reopen')->whereUuid('incident')->name('reopen')->can('reopen', 'incident');
    Route::post('{incident}/legal-hold', 'legalHold')->whereUuid('incident')->name('legal-hold')->can('legalHold', 'incident');
});
