<?php

namespace App\DataTables\Admin\Provider;

use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\ProviderServiceCategory;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Services tab: the categories the provider asked for, their decision and who made it, with approve / reject actions
 * for an approved provider.
 */
final class ProviderServicesDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-services-table';

    protected array $defaultOrder = [[0, 'asc']];

    public function __construct(private readonly ProviderServiceRepositoryInterface $services)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ProviderServiceCategory>
     */
    public function query(): Builder
    {
        return $this->services->datatableQuery($this->provider());
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('service', __('admin.providers.services.service')),
            $this->column('status', __('admin.providers.fields.status'), orderable: true),
            $this->column('decided_at', __('admin.providers.services.decided_at'), orderable: true),
            $this->column('decision_reason', __('admin.providers.services.reason')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'service', 'status', 'decided_at', 'decision_reason', 'actions']);
        $table->addColumn('service', static fn (Model $row): string => $row instanceof ProviderServiceCategory ? (string) $row->serviceCategory->name : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'decided_at');
        $table->editColumn('decision_reason', static fn (Model $row): string => (string) ($row->getAttribute('decision_reason') ?? '—'));

        $provider = $this->provider();

        $this->actionsColumn($table, static fn (ProviderServiceCategory $service): array => [
            ...($service->status !== ServiceApprovalStatus::Approved ? [TableAction::confirm(
                __('admin.providers.actions.approve_service'),
                route('admin.providers.services.approve', ['provider' => $provider, 'serviceCategory' => $service->service_category_id]),
                'decideService',
                $provider,
                icon: 'check-circle',
                variant: 'success',
                reasonRequired: true,
                confirm: ['title' => __('admin.providers.confirm.approve_service_title'), 'message' => __('admin.providers.confirm.approve_service_message')],
                reloadTable: self::TABLE_ID,
            )] : []),
            ...($service->status !== ServiceApprovalStatus::Rejected ? [TableAction::confirm(
                __('admin.providers.actions.reject_service'),
                route('admin.providers.services.reject', ['provider' => $provider, 'serviceCategory' => $service->service_category_id]),
                'decideService',
                $provider,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.providers.confirm.reject_service_title'), 'message' => __('admin.providers.confirm.reject_service_message')],
                reloadTable: self::TABLE_ID,
            )] : []),
        ]);
    }
}
