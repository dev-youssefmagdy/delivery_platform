<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\CampaignFilterData;
use App\Models\ReferralCampaign;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Referral campaigns (filters: audience, status, start range).
 */
final class ReferralCampaignsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'referral-campaigns-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly ReferralRepositoryInterface $referrals, private readonly MoneyFormatter $money, private readonly CurrencyRegistry $currencies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ReferralCampaign>
     */
    public function query(): Builder
    {
        return $this->referrals->campaignsQuery(CampaignFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'referral_campaigns.id'),
            $this->column('name', __('growth.fields.name')),
            $this->column('audience', __('growth.fields.audience'), orderable: true, name: 'referral_campaigns.audience'),
            $this->column('status', __('growth.fields.status'), orderable: true, name: 'referral_campaigns.status'),
            $this->column('rewards', __('growth.referral.fields.rewards')),
            $this->column('budget', __('growth.fields.budget_used')),
            $this->column('starts_at', __('growth.fields.starts_at'), orderable: true, name: 'referral_campaigns.starts_at'),
            $this->column('ends_at', __('growth.fields.ends_at'), orderable: true, name: 'referral_campaigns.ends_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'name', 'audience', 'status', 'rewards', 'budget', 'starts_at', 'ends_at', 'actions']);

        $table->addColumn('name', static fn (ReferralCampaign $campaign): string => '<a href="'.e(route('admin.referral-campaigns.show', $campaign)).'" class="font-medium text-brand-700 hover:underline">'.e($campaign->displayName()).'</a>');
        $this->rawColumn('name');
        $this->enumBadgeColumn($table, 'audience');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('rewards', fn (ReferralCampaign $campaign): string => e($this->format($campaign->referrer_reward_minor, $campaign->currency).' / '.$this->format($campaign->referee_reward_minor, $campaign->currency)));
        $table->addColumn('budget', fn (ReferralCampaign $campaign): string => e($this->format($campaign->spent_minor, $campaign->currency).' / '.$this->format($campaign->budget_minor, $campaign->currency)));
        $this->dateColumn($table, 'starts_at');
        $this->dateColumn($table, 'ends_at');

        $this->actionsColumn($table, static fn (ReferralCampaign $campaign): array => [TableAction::view(route('admin.referral-campaigns.show', $campaign), $campaign), TableAction::edit(route('admin.referral-campaigns.edit', $campaign), $campaign)]);
    }

    private function format(int $minor, string $currency): string
    {
        return $this->money->format(Money::of($minor, $this->currencies->get($currency)));
    }
}
