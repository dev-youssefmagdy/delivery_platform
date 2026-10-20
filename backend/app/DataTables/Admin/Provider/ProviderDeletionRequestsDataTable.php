<?php

namespace App\DataTables\Admin\Provider;

use App\Enums\Identity\DeletionRequestStatus;
use App\Models\AccountDeletionRequest;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Data requests tab of a provider: account-deletion requests with approve / reject on pending ones (the legal action
 * of `customers.process_deletion`; approving anonymises the account, ADR-77).
 */
final class ProviderDeletionRequestsDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-deletion-requests-table';

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
        $user = $this->provider()->user;

        return $this->requests->datatableQuery($user);
    }

    protected function columns(): array
    {
        return [
            $this->column('requested_at', __('admin.customers.deletion.requested_at'), orderable: true),
            $this->column('status', __('admin.providers.fields.status'), orderable: true),
            $this->column('reason', __('admin.providers.deletion.provider_reason')),
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

        $provider = $this->provider();

        $this->actionsColumn($table, static fn (AccountDeletionRequest $request): array => $request->status === DeletionRequestStatus::Pending ? [
            TableAction::confirm(
                __('admin.providers.actions.approve_deletion'),
                route('admin.providers.deletion-requests.approve', ['provider' => $provider, 'deletionRequest' => $request->uuid]),
                'processDeletion',
                $provider,
                icon: 'check-circle',
                reasonRequired: true,
                confirm: ['title' => __('admin.providers.confirm.approve_deletion_title'), 'message' => __('admin.providers.confirm.approve_deletion_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.providers.actions.reject_deletion'),
                route('admin.providers.deletion-requests.reject', ['provider' => $provider, 'deletionRequest' => $request->uuid]),
                'processDeletion',
                $provider,
                icon: 'x-mark',
                variant: 'secondary',
                reasonRequired: true,
                confirm: ['title' => __('admin.providers.confirm.reject_deletion_title'), 'message' => __('admin.providers.confirm.reject_deletion_message')],
                reloadTable: self::TABLE_ID,
            ),
        ] : []);
    }
}
