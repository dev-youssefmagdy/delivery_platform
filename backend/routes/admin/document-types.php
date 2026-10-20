<?php

use App\Http\Controllers\Admin\Provider\DocumentTypeController;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Route;

/*
| Document types and requirements (documents.manage_types). Loaded by routes/admin.php.
*/
Route::prefix('document-types')->name('document-types.')->controller(DocumentTypeController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', DocumentType::class);
    Route::get('create', 'create')->name('create')->can('create', DocumentType::class);
    Route::post('/', 'store')->name('store')->can('create', DocumentType::class);
    Route::get('{documentType}', 'show')->whereNumber('documentType')->name('show')->can('view', 'documentType');
    Route::get('{documentType}/activity', 'activity')->whereNumber('documentType')->name('activity')->can('view', 'documentType');
    Route::get('{documentType}/requirements', 'requirements')->whereNumber('documentType')->name('requirements')->can('view', 'documentType');
    Route::post('{documentType}/requirements', 'storeRequirement')->whereNumber('documentType')->name('requirements.store')->can('update', 'documentType');
    Route::delete('{documentType}/requirements/{requirement}', 'destroyRequirement')->whereNumber(['documentType', 'requirement'])->name('requirements.destroy')->can('update', 'documentType');
    Route::get('{documentType}/edit', 'edit')->whereNumber('documentType')->name('edit')->can('update', 'documentType');
    Route::put('{documentType}', 'update')->whereNumber('documentType')->name('update')->can('update', 'documentType');
    Route::post('{documentType}/activate', 'activate')->whereNumber('documentType')->name('activate')->can('activate', 'documentType');
    Route::post('{documentType}/deactivate', 'deactivate')->whereNumber('documentType')->name('deactivate')->can('deactivate', 'documentType');
    Route::delete('{documentType}', 'destroy')->whereNumber('documentType')->name('destroy')->can('delete', 'documentType');
});
