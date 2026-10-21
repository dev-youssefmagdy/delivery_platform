<?php

namespace App\DataTables\Admin\Pricing;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Pricing\FarePolicyFilterData;
use App\Enums\Pricing\FarePolicyState;
use App\Models\FarePolicy;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Fare policies listing (filters: service, zone, enabled, in effect on a date).
 */
final class FarePoliciesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'fare-policies-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly FarePolicyRepositoryInterface $policies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<FarePolicy>
     */
    public function query(): Builder
    {
        return $this->policies->datatableQuery(FarePolicyFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('service', __('admin.fare_policies.fields.service')),
            $this->column('zone', __('admin.fare_policies.fields.zone')),
            $this->column('base_minor', __('admin.fare_policies.fields.base'), orderable: true),
            $this->column('per_km_minor', __('admin.fare_policies.fields.per_km'), orderable: true),
            $this->column('per_min_minor', __('admin.fare_policies.fields.per_min')),
            $this->column('min_fare_minor', __('admin.fare_policies.fields.min_fare'), orderable: true),
            $this->column('effective_from', __('admin.fare_policies.fields.effective_from'), orderable: true),
            $this->column('effective_to', __('admin.fare_policies.fields.effective_to')),
            $this->column('state', __('admin.fare_policies.fields.state')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $formatter = app(MoneyFormatter::class);
        $currencies = app(CurrencyRegistry::class);

        $table->addColumn('service', static fn (FarePolicy $policy): string => (string) $policy->serviceCategory->name);
        $table->addColumn('zone', static fn (FarePolicy $policy): string => $policy->serviceZone === null ? (string) __('admin.fare_policies.all_zones') : (string) $policy->serviceZone->name);

        foreach (['base_minor', 'per_km_minor', 'per_min_minor', 'min_fare_minor'] as $column) {
            $table->editColumn($column, static fn (Model $row): string => $row instanceof FarePolicy
                ? e($formatter->format(Money::of((int) $row->getAttribute($column), $currencies->get($row->currency))))
                : '');
        }

        $this->dateColumn($table, 'effective_from');
        $table->editColumn('effective_to', fn (Model $row): string => $row instanceof FarePolicy && $row->effective_to !== null ? $this->formatDate($row->effective_to) : '—');
        $table->addColumn('state', static fn (FarePolicy $policy): string => Blade::renderComponent(new Badge(FarePolicyState::of($policy))));
        $this->rawColumn('state');

        $this->actionsColumn($table, fn (FarePolicy $policy): array => [
            TableAction::view(route('admin.fare-policies.show', $policy), $policy),
            TableAction::edit(route('admin.fare-policies.edit', $policy), $policy),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.fare-policies.activate', $policy),
                'activate',
                $policy,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.fare_policies.confirm.activate_title'), 'message' => __('admin.fare_policies.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.fare-policies.deactivate', $policy),
                'deactivate',
                $policy,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.fare_policies.confirm.deactivate_title'), 'message' => __('admin.fare_policies.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.fare-policies.destroy', $policy), $policy, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
