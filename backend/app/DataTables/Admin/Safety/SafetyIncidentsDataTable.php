<?php

namespace App\DataTables\Admin\Safety;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Safety\IncidentFilterData;
use App\Enums\Admin\AdminPermission;
use App\Models\SafetyIncident;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Safety Center queue (filters: severity, status, category, assigned officer, SLA breached, date range, number, priority, trigger,
 * booking, legal hold, and the quick scopes urgent / mine / unassigned). The default order puts the most urgent open case first.
 * No narrative, phone or location is ever a column: the queue shows structure only (`incidents.view`).
 */
class SafetyIncidentsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'safety-incidents-table';

    protected array $defaultOrder = [[8, 'asc']];

    public function __construct(protected readonly SafetyIncidentRepositoryInterface $incidents)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<SafetyIncident>
     */
    public function query(): Builder
    {
        return $this->incidents->datatableQuery(IncidentFilterData::fromRequest($this->filterRequest()));
    }

    public function exportPermission(): AdminPermission
    {
        return AdminPermission::IncidentsExportRestricted;
    }

    protected function columns(): array
    {
        return [
            $this->column('number', __('safety.admin.fields.number'), orderable: true, searchable: true, name: 'safety_incidents.number'),
            $this->column('trigger', __('safety.admin.fields.trigger'), orderable: true, name: 'safety_incidents.trigger'),
            $this->column('category', __('safety.admin.fields.category'), orderable: true, name: 'safety_incidents.category'),
            $this->column('severity', __('safety.admin.fields.severity'), orderable: true, name: 'safety_incidents.severity'),
            $this->column('priority', __('safety.admin.fields.priority'), orderable: true, name: 'safety_incidents.priority'),
            $this->column('status', __('safety.admin.fields.status'), orderable: true, name: 'safety_incidents.status'),
            $this->column('reporter', __('safety.admin.fields.reporter')),
            $this->column('booking', __('safety.admin.fields.booking')),
            $this->column('sla_due_at', __('safety.admin.fields.sla'), orderable: true, name: 'safety_incidents.sla_due_at'),
            $this->column('assignee', __('safety.admin.fields.assignee')),
            $this->column('created_at', __('admin.common.created_at'), orderable: true, name: 'safety_incidents.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['number', 'trigger', 'category', 'severity', 'priority', 'status', 'reporter', 'booking', 'sla_due_at', 'assignee', 'created_at', 'actions']);

        $table->editColumn('number', static fn (Model $row): string => $row instanceof SafetyIncident ? '<a href="'.e(route('admin.safety-incidents.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e((string) $row->number).'</a>'.($row->legal_hold ? ' <span title="'.e(__('safety.admin.legal_hold')).'">&#128274;</span>' : '') : '');
        $this->rawColumn('number');
        $table->editColumn('trigger', static fn (Model $row): string => $row instanceof SafetyIncident ? e($row->trigger->label()) : '');
        $table->editColumn('category', static fn (Model $row): string => $row instanceof SafetyIncident ? e($row->category->label()) : '');
        $this->enumBadgeColumn($table, 'severity');
        $this->enumBadgeColumn($table, 'priority');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('reporter', static fn (SafetyIncident $incident): string => e(($incident->reporter->name ?? '—').' · '.$incident->reporter_type->label()));
        $table->addColumn('booking', static fn (SafetyIncident $incident): string => $incident->booking === null ? '—' : e($incident->booking->code));
        $table->addColumn('assignee', static fn (SafetyIncident $incident): string => e($incident->assignedAdmin->name ?? '—'));
        $table->editColumn('sla_due_at', function (Model $row): string {
            if (! $row instanceof SafetyIncident || $row->sla_due_at === null) {
                return '—';
            }

            if ($row->acknowledged_at !== null) {
                return e(__('safety.admin.sla_met'));
            }

            $overdue = $row->sla_due_at->isPast();
            $text = $overdue
                ? __('safety.admin.sla_overdue', ['time' => $row->sla_due_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)])
                : __('safety.admin.sla_in', ['time' => $row->sla_due_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)]);

            return '<span class="'.($overdue ? 'font-medium text-danger-700' : 'text-slate-700').'" data-sla-due="'.e($row->sla_due_at->toIso8601String()).'">'.e($text).'</span>';
        });
        $this->rawColumn('sla_due_at');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (SafetyIncident $incident): array => [
            TableAction::view(route('admin.safety-incidents.show', $incident), $incident),
        ]);
    }

    /**
     * Restricted export: structure and timing only (no narrative, phone, location or notes).
     *
     * @return array<string, string>
     */
    public function exportHeadings(): array
    {
        return [
            'number' => __('safety.admin.fields.number'), 'trigger' => __('safety.admin.fields.trigger'), 'category' => __('safety.admin.fields.category'),
            'severity' => __('safety.admin.fields.severity'), 'priority' => __('safety.admin.fields.priority'), 'status' => __('safety.admin.fields.status'),
            'booking' => __('safety.admin.fields.booking'), 'occurred_at' => __('safety.admin.fields.occurred_at'), 'acknowledged_at' => __('safety.admin.fields.acknowledged_at'),
            'resolved_at' => __('safety.admin.fields.resolved_at'), 'outcome' => __('safety.admin.fields.outcome'), 'legal_hold' => __('safety.admin.legal_hold'),
        ];
    }

    public function exportRow(Model $row): array
    {
        if (! $row instanceof SafetyIncident) {
            return [];
        }

        return array_map(fn (mixed $value): string => $this->stringify($value, latinDigits: true), [
            $row->number, $row->trigger, $row->category, $row->severity, $row->priority, $row->status, $row->booking?->code, $row->occurred_at,
            $row->acknowledged_at, $row->resolved_at, $row->outcome, $row->legal_hold,
        ]);
    }
}
