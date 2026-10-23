<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\CampaignFilterData;
use App\Models\IncentiveCampaign;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Provider incentive campaigns (filters: status, start range).
 */
final class IncentiveCampaignsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'incentive-campaigns-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly IncentiveRepositoryInterface $incentives, private readonly MoneyFormatter $money, private readonly CurrencyRegistry $currencies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<IncentiveCampaign>
     */
    public function query(): Builder
    {
        return $this->incentives->campaignsQuery(CampaignFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'incentive_campaigns.id'),
            $this->column('name', __('growth.fields.name')),
            $this->column('status', __('growth.fields.status'), orderable: true, name: 'incentive_campaigns.status'),
            $this->column('quest', __('growth.incentive.fields.quest')),
            $this->column('budget', __('growth.fields.budget_used')),
            $this->column('starts_at', __('growth.fields.starts_at'), orderable: true, name: 'incentive_campaigns.starts_at'),
            $this->column('ends_at', __('growth.fields.ends_at'), orderable: true, name: 'incentive_campaigns.ends_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'name', 'status', 'quest', 'budget', 'starts_at', 'ends_at', 'actions']);

        $table->addColumn('name', static fn (IncentiveCampaign $campaign): string => '<a href="'.e(route('admin.incentive-campaigns.show', $campaign)).'" class="font-medium text-brand-700 hover:underline">'.e($campaign->displayName()).'</a>');
        $this->rawColumn('name');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('quest', fn (IncentiveCampaign $campaign): string => e(__('growth.incentive.quest_line', ['jobs' => $campaign->target_jobs, 'bonus' => $this->format($campaign->bonus_minor, $campaign->currency)])));
        $table->addColumn('budget', fn (IncentiveCampaign $campaign): string => e($this->format($campaign->spent_minor, $campaign->currency).' / '.$this->format($campaign->budget_minor, $campaign->currency)));
        $this->dateColumn($table, 'starts_at');
        $this->dateColumn($table, 'ends_at');

        $this->actionsColumn($table, static fn (IncentiveCampaign $campaign): array => [TableAction::view(route('admin.incentive-campaigns.show', $campaign), $campaign), TableAction::edit(route('admin.incentive-campaigns.edit', $campaign), $campaign)]);
    }

    private function format(int $minor, string $currency): string
    {
        return $this->money->format(Money::of($minor, $this->currencies->get($currency)));
    }
}
