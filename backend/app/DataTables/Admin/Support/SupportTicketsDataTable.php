<?php

namespace App\DataTables\Admin\Support;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Support\TicketFilterData;
use App\DTOs\Support\TicketVisibilityScope;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Ticket inbox (filters: number, status, priority, category, group, assignee, SLA breached, booking, created range, and the
 * "my tickets" / "unassigned" quick filters). The query is restricted to what the signed-in admin may see.
 */
class SupportTicketsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'support-tickets-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(protected readonly SupportTicketRepositoryInterface $tickets)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<SupportTicket>
     */
    public function query(): Builder
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            throw new AuthenticationException;
        }

        return $this->tickets->datatableQuery($this->filters(), TicketVisibilityScope::for($admin));
    }

    protected function filters(): TicketFilterData
    {
        return TicketFilterData::fromRequest($this->filterRequest());
    }

    protected function columns(): array
    {
        return [
            $this->column('number', __('admin.support_tickets.fields.number'), orderable: true, searchable: true, name: 'support_tickets.number'),
            $this->column('subject', __('admin.support_tickets.fields.subject'), searchable: true, name: 'support_tickets.subject'),
            $this->column('category', __('admin.support_tickets.fields.category'), orderable: true, name: 'support_tickets.category'),
            $this->column('priority', __('admin.support_tickets.fields.priority'), orderable: true, name: 'support_tickets.priority'),
            $this->column('status', __('admin.support_tickets.fields.status'), orderable: true, name: 'support_tickets.status'),
            $this->column('requester', __('admin.support_tickets.fields.requester')),
            $this->column('booking', __('admin.support_tickets.fields.booking')),
            $this->column('assignee', __('admin.support_tickets.fields.assignee')),
            $this->column('group', __('admin.support_tickets.fields.group'), orderable: true, name: 'support_tickets.group'),
            $this->column('sla_due_at', __('admin.support_tickets.fields.sla_due_at'), orderable: true, name: 'support_tickets.sla_due_at'),
            $this->column('last_message_at', __('admin.support_tickets.fields.last_message_at'), orderable: true, name: 'support_tickets.last_message_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['number', 'subject', 'category', 'priority', 'status', 'requester', 'booking', 'assignee', 'group', 'sla_due_at', 'last_message_at', 'actions']);

        $table->editColumn('number', static fn (Model $row): string => $row instanceof SupportTicket ? '<a href="'.e(route('admin.support-tickets.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e((string) $row->number).'</a>' : '');
        $this->rawColumn('number');
        $table->editColumn('subject', static fn (Model $row): string => $row instanceof SupportTicket ? e(\Illuminate\Support\Str::limit($row->subject, 60)) : '');
        $table->editColumn('category', static fn (Model $row): string => $row instanceof SupportTicket ? e($row->category->label()) : '');
        $this->enumBadgeColumn($table, 'priority');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('requester', static fn (SupportTicket $ticket): string => e(($ticket->user->name ?? '—').' · '.$ticket->app->label()));
        $table->addColumn('booking', static fn (SupportTicket $ticket): string => $ticket->booking === null ? '—' : e($ticket->booking->code));
        $table->addColumn('assignee', static fn (SupportTicket $ticket): string => e($ticket->assignedAdmin?->name ?? '—'));
        $table->editColumn('group', static fn (Model $row): string => $row instanceof SupportTicket ? e($row->group->label()) : '');
        $table->editColumn('sla_due_at', function (Model $row): string {
            if (! $row instanceof SupportTicket || $row->sla_due_at === null) {
                return '—';
            }

            $text = e($this->formatDate($row->sla_due_at));

            return $row->sla_breached_at !== null || $row->isSlaBreached()
                ? '<span class="font-medium text-danger-700">'.$text.' · '.e(__('admin.support_tickets.sla_breached')).'</span>'
                : $text;
        });
        $this->rawColumn('sla_due_at');
        $this->dateColumn($table, 'last_message_at');

        $this->actionsColumn($table, static fn (SupportTicket $ticket): array => [
            TableAction::view(route('admin.support-tickets.show', $ticket), $ticket),
        ]);
    }
}
