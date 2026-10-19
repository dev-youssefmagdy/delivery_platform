<?php

namespace App\Http\Controllers\Admin\Audit;

use App\DataTables\Admin\Audit\AuditLogsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\AdminAuditLog;
use App\Services\Admin\AuditLogService;
use Illuminate\Contracts\View\View;

/**
 * Read-only audit trail (audit_logs.view) with a before/after diff viewer.
 */
final class AuditLogController extends AdminController
{
    public function __construct(private readonly AuditLogService $service) {}

    public function index(AuditLogsDataTable $table): mixed
    {
        return $table->render('admin.audit-logs.index', ['targetTypes' => $this->service->targetTypeOptions()]);
    }

    public function show(AdminAuditLog $auditLog): View
    {
        return view('admin.audit-logs.show', ['details' => $this->service->details((int) $auditLog->getKey())]);
    }
}
