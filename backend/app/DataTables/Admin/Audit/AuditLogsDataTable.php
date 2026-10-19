<?php

namespace App\DataTables\Admin\Audit;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Admin\AuditLogFilterData;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Services\Admin\AuditLogService;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Read-only audit trail (filters: actor, action, target type, target id, date range, has reason).
 * Global search is off; before/after payloads are only visible on the show page.
 */
final class AuditLogsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'audit-logs-table';

    protected bool $globalSearch = false;

    public function __construct(
        private readonly AuditLogRepositoryInterface $logs,
        private readonly AuditLogService $service,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<AdminAuditLog>
     */
    public function query(): Builder
    {
        return $this->logs->datatableQuery(AuditLogFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.audit_logs.fields.time'), orderable: true),
            $this->column('actor', __('admin.audit_logs.fields.actor')),
            $this->column('action', __('admin.audit_logs.fields.action'), orderable: true),
            $this->column('auditable_type', __('admin.audit_logs.fields.target'), orderable: true),
            $this->column('reason', __('admin.audit_logs.fields.reason')),
            $this->column('ip', __('admin.audit_logs.fields.ip')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        // Snapshots, request metadata and the browser are only shown on the entry's own page.
        $table->removeColumn('before', 'after', 'meta', 'user_agent', 'request_id', 'actor_type', 'actor_id');

        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'action');

        $table->addColumn('actor', fn (Model $row): string => $row instanceof AdminAuditLog ? ($this->service->actorName($row) ?? '—') : '—');
        $table->editColumn('auditable_type', static fn (Model $row): string => $row instanceof AdminAuditLog
            ? $row->auditable_type.($row->auditable_id !== null ? ' #'.$row->auditable_id : '')
            : '');
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof AdminAuditLog && $row->reason !== null ? Str::limit($row->reason, 80) : '—');
        $table->editColumn('ip', static fn (Model $row): string => (string) ($row->getAttribute('ip') ?? '—'));

        $this->actionsColumn($table, static fn (AdminAuditLog $log): array => [
            TableAction::view(route('admin.audit-logs.show', $log), $log),
        ]);
    }
}
