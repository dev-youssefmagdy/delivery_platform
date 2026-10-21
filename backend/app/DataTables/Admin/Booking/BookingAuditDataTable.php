<?php

namespace App\DataTables\Admin\Booking;

use App\DTOs\Admin\AuditLogFilterData;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Services\Admin\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Audit tab of the booking page: every operator action recorded against the booking (overrides with their reasons, PII views).
 */
final class BookingAuditDataTable extends BookingTabDataTable
{
    public const TABLE_ID = 'booking-audit-table';

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[0, 'desc']];

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
        $booking = $this->booking();

        return $this->logs->datatableQuery(new AuditLogFilterData(targetType: $booking->getMorphClass(), targetId: (string) $booking->getKey()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.audit_logs.fields.time'), orderable: true),
            $this->column('action', __('admin.audit_logs.fields.action'), orderable: true),
            $this->column('actor', __('admin.audit_logs.fields.actor')),
            $this->column('override', __('admin.bookings.audit.override')),
            $this->column('reason', __('admin.audit_logs.fields.reason')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'created_at', 'action', 'actor', 'override', 'reason']);
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'action');
        $table->addColumn('actor', fn (Model $row): string => $row instanceof AdminAuditLog ? ($this->service->actorName($row) ?? '—') : '—');
        $table->addColumn('override', static function (Model $row): string {
            $value = $row instanceof AdminAuditLog ? ($row->meta['override'] ?? null) : null;

            return is_string($value) ? (string) __("enums.booking_override_action.{$value}") : '—';
        });
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof AdminAuditLog && $row->reason !== null ? Str::limit($row->reason, 160) : '—');
    }
}
