<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\LoyaltyLevelFilterData;
use App\Models\LoyaltyLevel;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Loyalty levels (filter: active). Deactivating needs a reason (confirm dialog).
 */
final class LoyaltyLevelsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'loyalty-levels-table';

    protected array $defaultOrder = [[1, 'asc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly LoyaltyRepositoryInterface $loyalty, private readonly MoneyFormatter $money, private readonly CurrencyRegistry $currencies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<LoyaltyLevel>
     */
    public function query(): Builder
    {
        return $this->loyalty->levelsQuery(LoyaltyLevelFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('name', __('growth.fields.name')),
            $this->column('min_completed_jobs', __('growth.loyalty.fields.min_jobs'), orderable: true, name: 'loyalty_levels.min_completed_jobs'),
            $this->column('reward', __('growth.loyalty.fields.reward')),
            $this->column('credit_valid_days', __('growth.loyalty.fields.valid_days')),
            $this->column('is_active', __('growth.fields.active'), orderable: true, name: 'loyalty_levels.is_active'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['name', 'min_completed_jobs', 'reward', 'credit_valid_days', 'is_active', 'actions']);

        $table->addColumn('name', static fn (LoyaltyLevel $level): string => e($level->displayName()));
        $table->addColumn('reward', fn (LoyaltyLevel $level): string => e($level->reward_minor > 0 ? $this->money->format(Money::of($level->reward_minor, $this->currencies->get($level->currency))) : '—'));
        $this->boolColumn($table, 'is_active');

        $this->actionsColumn($table, static fn (LoyaltyLevel $level): array => [
            TableAction::edit(route('admin.loyalty-levels.edit', $level), $level),
            TableAction::confirm(
                $level->is_active ? __('growth.loyalty.actions.deactivate') : __('growth.loyalty.actions.activate'),
                route('admin.loyalty-levels.toggle', [$level, 'active' => $level->is_active ? 0 : 1]),
                'toggle',
                $level,
                icon: $level->is_active ? 'x-mark' : 'check',
                variant: $level->is_active ? 'danger' : 'secondary',
                reasonRequired: $level->is_active,
                confirm: ['title' => __('growth.loyalty.confirm.title'), 'message' => __('growth.loyalty.confirm.message'), 'confirm_label' => $level->is_active ? __('growth.loyalty.actions.deactivate') : __('growth.loyalty.actions.activate')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
