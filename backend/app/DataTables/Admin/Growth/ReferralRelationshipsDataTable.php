<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\RelationshipFilterData;
use App\Enums\Growth\FraudFlag;
use App\Models\ReferralRelationship;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Referral relationships (filters: campaign, audience, status, flagged only, created range). Names only; no contact data is listed.
 */
final class ReferralRelationshipsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'referral-relationships-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly ReferralRepositoryInterface $referrals)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ReferralRelationship>
     */
    public function query(): Builder
    {
        return $this->referrals->relationshipsQuery(RelationshipFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'referral_relationships.id'),
            $this->column('campaign', __('growth.fields.campaign')),
            $this->column('audience', __('growth.fields.audience'), orderable: true, name: 'referral_relationships.audience'),
            $this->column('referrer', __('growth.referral.fields.referrer')),
            $this->column('referee', __('growth.referral.fields.referee')),
            $this->column('status', __('growth.fields.status'), orderable: true, name: 'referral_relationships.status'),
            $this->column('jobs_completed', __('growth.referral.fields.jobs'), orderable: true, name: 'referral_relationships.jobs_completed'),
            $this->column('flags', __('growth.fields.flags')),
            $this->column('created_at', __('growth.fields.created'), orderable: true, name: 'referral_relationships.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'campaign', 'audience', 'referrer', 'referee', 'status', 'jobs_completed', 'flags', 'created_at', 'actions']);

        $table->addColumn('campaign', static fn (ReferralRelationship $row): string => e($row->campaign->displayName()));
        $this->enumBadgeColumn($table, 'audience');
        $table->addColumn('referrer', static fn (ReferralRelationship $row): string => e($row->referrer->name ?? '—'));
        $table->addColumn('referee', static fn (ReferralRelationship $row): string => e($row->referee->name ?? '—'));
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('flags', static fn (ReferralRelationship $row): string => e(implode('، ', array_map(static fn (string $flag): string => FraudFlag::tryFrom($flag)?->label() ?? $flag, $row->flagKeys())) ?: '—'));
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (ReferralRelationship $row): array => [TableAction::view(route('admin.referral-relationships.show', $row), $row)]);
    }
}
