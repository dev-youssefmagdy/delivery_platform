<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Finance\CommissionRuleFilterData;
use App\Enums\Finance\CommissionRuleState;
use App\Models\CommissionRule;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Finance\CommissionTermsPresenter;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Commission rules listing (filters: service, zone, type, enabled, in effect on a date).
 */
final class CommissionRulesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'commission-rules-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CommissionRuleRepositoryInterface $rules)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CommissionRule>
     */
    public function query(): Builder
    {
        return $this->rules->datatableQuery(CommissionRuleFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.commission_rules.fields.name'), orderable: true),
            $this->column('service', __('admin.commission_rules.fields.service')),
            $this->column('zone', __('admin.commission_rules.fields.zone')),
            $this->column('terms', __('admin.commission_rules.fields.terms')),
            $this->column('priority', __('admin.commission_rules.fields.priority'), orderable: true),
            $this->column('effective_from', __('admin.commission_rules.fields.effective_from'), orderable: true),
            $this->column('effective_to', __('admin.commission_rules.fields.effective_to')),
            $this->column('state', __('admin.commission_rules.fields.state')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $presenter = app(CommissionTermsPresenter::class);

        $table->addColumn('service', static fn (CommissionRule $rule): string => $rule->serviceCategory === null ? (string) __('admin.commission_rules.all_services') : (string) $rule->serviceCategory->name);
        $table->addColumn('zone', static fn (CommissionRule $rule): string => $rule->serviceZone === null ? (string) __('admin.commission_rules.all_zones') : (string) $rule->serviceZone->name);
        $table->addColumn('terms', static fn (CommissionRule $rule): string => $rule->currentVersion === null ? '—' : e($presenter->describe($rule->currentVersion, $rule->currency)));
        $this->rawColumn('terms');
        $this->dateColumn($table, 'effective_from');
        $table->editColumn('effective_to', fn (Model $row): string => $row instanceof CommissionRule && $row->effective_to !== null ? $this->formatDate($row->effective_to) : '—');
        $table->addColumn('state', static fn (CommissionRule $rule): string => Blade::renderComponent(new Badge(CommissionRuleState::of($rule))));
        $this->rawColumn('state');

        $this->actionsColumn($table, fn (CommissionRule $rule): array => [
            TableAction::view(route('admin.commission-rules.show', $rule), $rule),
            TableAction::edit(route('admin.commission-rules.edit', $rule), $rule),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.commission-rules.activate', $rule),
                'activate',
                $rule,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.commission_rules.confirm.activate_title'), 'message' => __('admin.commission_rules.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.commission-rules.deactivate', $rule),
                'deactivate',
                $rule,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.commission_rules.confirm.deactivate_title'), 'message' => __('admin.commission_rules.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
