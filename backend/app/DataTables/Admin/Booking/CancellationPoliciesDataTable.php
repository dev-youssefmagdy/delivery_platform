<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Booking\CancellationPolicyFilterData;
use App\Enums\Booking\CancellationPolicyState;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Cancellation policies listing (filters: name, service, zone, enabled, in effect on a date).
 */
final class CancellationPoliciesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'cancellation-policies-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CancellationPolicyRepositoryInterface $policies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CancellationPolicy>
     */
    public function query(): Builder
    {
        return $this->policies->datatableQuery(CancellationPolicyFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.cancellation_policies.fields.name'), orderable: true),
            $this->column('service', __('admin.cancellation_policies.fields.service')),
            $this->column('zone', __('admin.cancellation_policies.fields.zone')),
            $this->column('free_grace_seconds', __('admin.cancellation_policies.fields.free_grace'), orderable: true),
            $this->column('free_wait_seconds', __('admin.cancellation_policies.fields.free_wait')),
            $this->column('effective_from', __('admin.cancellation_policies.fields.effective_from'), orderable: true),
            $this->column('effective_to', __('admin.cancellation_policies.fields.effective_to')),
            $this->column('state', __('admin.cancellation_policies.fields.state')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->addColumn('service', static fn (CancellationPolicy $policy): string => $policy->serviceCategory === null ? (string) __('admin.cancellation_policies.all_services') : (string) $policy->serviceCategory->name);
        $table->addColumn('zone', static fn (CancellationPolicy $policy): string => $policy->serviceZone === null ? (string) __('admin.cancellation_policies.all_zones') : (string) $policy->serviceZone->name);
        $table->editColumn('free_grace_seconds', static fn (Model $row): string => $row instanceof CancellationPolicy ? __('admin.cancellation_policies.seconds', ['count' => $row->free_grace_seconds]) : '');
        $table->editColumn('free_wait_seconds', static fn (Model $row): string => $row instanceof CancellationPolicy ? __('admin.cancellation_policies.seconds', ['count' => $row->free_wait_seconds]) : '');

        $this->dateColumn($table, 'effective_from');
        $table->editColumn('effective_to', fn (Model $row): string => $row instanceof CancellationPolicy && $row->effective_to !== null ? $this->formatDate($row->effective_to) : '—');
        $table->addColumn('state', static fn (CancellationPolicy $policy): string => Blade::renderComponent(new Badge(CancellationPolicyState::of($policy))));
        $this->rawColumn('state');

        $this->actionsColumn($table, fn (CancellationPolicy $policy): array => [
            TableAction::view(route('admin.cancellation-policies.show', $policy), $policy),
            TableAction::edit(route('admin.cancellation-policies.edit', $policy), $policy),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.cancellation-policies.activate', $policy),
                'activate',
                $policy,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.cancellation_policies.confirm.activate_title'), 'message' => __('admin.cancellation_policies.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.cancellation-policies.deactivate', $policy),
                'deactivate',
                $policy,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.cancellation_policies.confirm.deactivate_title'), 'message' => __('admin.cancellation_policies.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.cancellation-policies.destroy', $policy), $policy, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
