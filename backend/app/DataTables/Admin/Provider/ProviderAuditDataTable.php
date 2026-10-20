<?php

namespace App\DataTables\Admin\Provider;

use App\DTOs\Admin\AuditLogFilterData;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Services\Admin\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Audit trail of one provider: decisions, suspensions, document reviews, every view or download of a document, reveals
 * of contact details and deletion processing. Snapshots are not shown here.
 */
final class ProviderAuditDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-audit-table';

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
        $provider = $this->provider();

        return $this->logs->datatableQuery(new AuditLogFilterData(targetType: $provider->getMorphClass(), targetId: (string) $provider->getKey()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.audit_logs.fields.time'), orderable: true),
            $this->column('action', __('admin.audit_logs.fields.action'), orderable: true),
            $this->column('actor', __('admin.audit_logs.fields.actor')),
            $this->column('reason', __('admin.audit_logs.fields.reason')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'created_at', 'action', 'actor', 'reason']);
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'action');
        $table->addColumn('actor', fn (Model $row): string => $row instanceof AdminAuditLog ? ($this->service->actorName($row) ?? '—') : '—');
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof AdminAuditLog && $row->reason !== null ? Str::limit($row->reason, 160) : '—');
    }
}
