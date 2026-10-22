<?php

use App\Http\Controllers\Admin\Support\SupportMacroController;
use App\Http\Controllers\Admin\Support\SupportTicketController;
use App\Models\SupportMacro;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Route;

/*
| Support (support.view / support.manage / support.view_all / macros.manage). Loaded by routes/admin.php.
| Ticket access is decided by SupportTicketPolicy (assigned / queue / team), not by the permission alone.
*/
Route::prefix('support-tickets')->name('support-tickets.')->controller(SupportTicketController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', SupportTicket::class);
    Route::get('{ticket}', 'show')->whereUuid('ticket')->name('show')->can('view', 'ticket');
    Route::get('{ticket}/activity', 'activity')->whereUuid('ticket')->name('activity')->can('view', 'ticket');
    Route::get('{ticket}/chat', 'chat')->whereUuid('ticket')->name('chat')->can('viewChat', 'ticket');
    Route::get('{ticket}/attachments/{attachment}', 'attachment')->whereUuid(['ticket', 'attachment'])->name('attachments.show')->can('view', 'ticket');
    Route::post('{ticket}/messages', 'reply')->whereUuid('ticket')->name('reply')->can('reply', 'ticket');
    Route::post('{ticket}/claim', 'claim')->whereUuid('ticket')->name('claim')->can('claim', 'ticket');
    Route::post('{ticket}/assign', 'assign')->whereUuid('ticket')->name('assign')->can('assign', 'ticket');
    Route::post('{ticket}/escalate', 'escalate')->whereUuid('ticket')->name('escalate')->can('escalate', 'ticket');
    Route::post('{ticket}/resolve', 'resolve')->whereUuid('ticket')->name('resolve')->can('resolve', 'ticket');
    Route::post('{ticket}/reopen', 'reopen')->whereUuid('ticket')->name('reopen')->can('reopen', 'ticket');
    Route::post('{ticket}/priority', 'priority')->whereUuid('ticket')->name('priority')->can('changePriority', 'ticket');
    Route::post('{ticket}/link-finance', 'linkFinance')->whereUuid('ticket')->name('link-finance')->can('linkFinance', 'ticket');
});

Route::prefix('macros')->name('macros.')->controller(SupportMacroController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', SupportMacro::class);
    Route::get('create', 'create')->name('create')->can('create', SupportMacro::class);
    Route::post('/', 'store')->name('store')->can('create', SupportMacro::class);
    Route::get('{macro}', 'show')->whereUuid('macro')->name('show')->can('view', 'macro');
    Route::get('{macro}/activity', 'activity')->whereUuid('macro')->name('activity')->can('view', 'macro');
    Route::get('{macro}/edit', 'edit')->whereUuid('macro')->name('edit')->can('update', 'macro');
    Route::put('{macro}', 'update')->whereUuid('macro')->name('update')->can('update', 'macro');
    Route::post('{macro}/activate', 'activate')->whereUuid('macro')->name('activate')->can('toggle', 'macro');
    Route::post('{macro}/deactivate', 'deactivate')->whereUuid('macro')->name('deactivate')->can('toggle', 'macro');
});
