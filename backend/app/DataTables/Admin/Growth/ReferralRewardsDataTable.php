<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\RewardFilterData;
use App\Models\ReferralReward;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Referral rewards, each one a ledger entry (filters: campaign, recipient, status, granted range).
 */
final class ReferralRewardsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'referral-rewards-table';

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
     * @return Builder<ReferralReward>
     */
    public function query(): Builder
    {
        return $this->referrals->rewardsQuery(RewardFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'referral_rewards.id'),
            $this->column('campaign', __('growth.fields.campaign')),
            $this->column('beneficiary', __('growth.rewards.fields.beneficiary')),
            $this->column('recipient', __('growth.rewards.fields.recipient'), orderable: true, name: 'referral_rewards.recipient'),
            $this->column('amount', __('growth.rewards.fields.amount'), orderable: true, name: 'referral_rewards.amount_minor'),
            $this->column('expired', __('growth.rewards.fields.expired')),
            $this->column('status', __('growth.fields.status'), orderable: true, name: 'referral_rewards.status'),
            $this->column('granted_at', __('growth.rewards.fields.granted'), orderable: true, name: 'referral_rewards.granted_at'),
            $this->column('expires_at', __('growth.rewards.fields.expires'), orderable: true, name: 'referral_rewards.expires_at'),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'campaign', 'beneficiary', 'recipient', 'amount', 'expired', 'status', 'granted_at', 'expires_at']);

        $table->addColumn('campaign', static fn (ReferralReward $row): string => e($row->campaign->displayName()));
        $table->addColumn('beneficiary', static fn (ReferralReward $row): string => '<a class="text-brand-700 hover:underline" href="'.e(route('admin.referral-relationships.show', $row->relationship)).'">'.e($row->user->name ?? '—').'</a>');
        $this->rawColumn('beneficiary');
        $this->enumBadgeColumn($table, 'recipient');
        $table->addColumn('amount', fn (ReferralReward $row): string => e($this->money->format(Money::of($row->amount_minor, $this->currencies->get($row->currency)))));
        $table->addColumn('expired', fn (ReferralReward $row): string => e($row->expired_minor > 0 ? $this->money->format(Money::of($row->expired_minor, $this->currencies->get($row->currency))) : '—'));
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'granted_at');
        $this->dateColumn($table, 'expires_at');
    }
}
