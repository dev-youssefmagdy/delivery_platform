<?php

namespace App\DataTables\Admin\Customers;

use App\Enums\Identity\DeletionRequestStatus;
use App\Models\AccountDeletionRequest;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Account-deletion (data) requests of a customer, with the approve / reject actions on pending ones.
 */
final class CustomerDeletionRequestsDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-deletion-requests-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(private readonly AccountDeletionRequestRepositoryInterface $requests)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<AccountDeletionRequest>
     */
    public function query(): Builder
    {
        return $this->requests->datatableQuery($this->customer());
    }

    protected function columns(): array
    {
        return [
            $this->column('requested_at', __('admin.customers.deletion.requested_at'), orderable: true),
            $this->column('status', __('admin.customers.fields.status'), orderable: true),
            $this->column('reason', __('admin.customers.deletion.customer_reason')),
            $this->column('processed_at', __('admin.customers.deletion.processed_at'), orderable: true),
            $this->column('processing_note', __('admin.customers.deletion.processing_note')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['requested_at', 'status', 'reason', 'processed_at', 'processing_note', 'actions']);
        $this->dateColumn($table, 'requested_at');
        $this->dateColumn($table, 'processed_at');
        $this->enumBadgeColumn($table, 'status');
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof AccountDeletionRequest && $row->reason !== null ? Str::limit($row->reason, 120) : '—');
        $table->editColumn('processing_note', static fn (Model $row): string => $row instanceof AccountDeletionRequest && $row->processing_note !== null ? Str::limit($row->processing_note, 120) : '—');

        $customer = $this->customer();

        $this->actionsColumn($table, static fn (AccountDeletionRequest $request): array => $request->status === DeletionRequestStatus::Pending ? [
            TableAction::confirm(
                __('admin.customers.actions.approve_deletion'),
                route('admin.customers.deletion-requests.approve', ['customer' => $customer, 'deletionRequest' => $request->uuid]),
                'processDeletion',
                $customer,
                icon: 'check-circle',
                reasonRequired: true,
                confirm: ['title' => __('admin.customers.confirm.approve_deletion_title'), 'message' => __('admin.customers.confirm.approve_deletion_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.customers.actions.reject_deletion'),
                route('admin.customers.deletion-requests.reject', ['customer' => $customer, 'deletionRequest' => $request->uuid]),
                'processDeletion',
                $customer,
                icon: 'x-mark',
                variant: 'neutral',
                reasonRequired: true,
                confirm: ['title' => __('admin.customers.confirm.reject_deletion_title'), 'message' => __('admin.customers.confirm.reject_deletion_message')],
                reloadTable: self::TABLE_ID,
            ),
        ] : []);
    }
}
