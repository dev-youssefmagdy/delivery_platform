<?php

use App\Http\Controllers\Admin\Audit\AuditLogController;
use App\Http\Controllers\Admin\Audit\LoginActivityController;
use App\Models\AdminAuditLog;
use App\Models\AdminLoginEvent;
use Illuminate\Support\Facades\Route;

/*
| Read-only audit trail (audit_logs.view) and login activity (login_activity.view). Loaded by routes/admin.php
| inside the authenticated admin group.
*/
Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->can('viewAny', AdminAuditLog::class);
Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->whereNumber('auditLog')->name('audit-logs.show')->can('view', 'auditLog');
Route::get('login-activity', [LoginActivityController::class, 'index'])->name('login-activity.index')->can('viewAny', AdminLoginEvent::class);
