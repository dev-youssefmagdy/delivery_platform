<?php

namespace App\DataTables\Admin\Growth;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Growth\ProgressFilterData;
use App\Enums\Growth\FraudFlag;
use App\Models\IncentiveProgress;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Provider progress of one incentive campaign (the page fixes the campaign; filter: status). Held rows can be released or rejected.
 */
final class IncentiveProgressDataTable extends AdminDataTable
{
    public const TABLE_ID = 'incentive-progress-table';

    protected array $defaultOrder = [[2, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly IncentiveRepositoryInterface $incentives)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<IncentiveProgress>
     */
    public function query(): Builder
    {
        $filters = ProgressFilterData::fromRequest($this->filterRequest());
        $campaign = request()->route('incentiveCampaign');

        return $this->incentives->progressQuery(new ProgressFilterData($campaign instanceof \App\Models\IncentiveCampaign ? $campaign->id : $filters->campaignId, $filters->statuses));
    }

    protected function columns(): array
    {
        return [
            $this->column('provider', __('growth.incentive.fields.provider')),
            $this->column('progress', __('growth.incentive.fields.progress')),
            $this->column('completed_jobs', __('growth.incentive.fields.jobs'), orderable: true, name: 'incentive_progress.completed_jobs'),
            $this->column('awards_count', __('growth.incentive.fields.awards'), orderable: true, name: 'incentive_progress.awards_count'),
            $this->column('status', __('growth.fields.status'), orderable: true, name: 'incentive_progress.status'),
            $this->column('flags', __('growth.fields.flags')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['provider', 'progress', 'completed_jobs', 'awards_count', 'status', 'flags', 'actions']);

        $table->addColumn('provider', static fn (IncentiveProgress $row): string => '<a class="text-brand-700 hover:underline" href="'.e(route('admin.providers.show', $row->provider)).'">'.e($row->provider->user->name ?? '—').'</a>');
        $this->rawColumn('provider');
        $table->addColumn('progress', static fn (IncentiveProgress $row): string => e(($row->completed_jobs % max(1, $row->campaign->target_jobs)).' / '.$row->campaign->target_jobs));
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('flags', static fn (IncentiveProgress $row): string => e(implode(__('admin.list_separator'), array_map(static fn (array $flag): string => FraudFlag::tryFrom($flag['flag'])?->label() ?? $flag['flag'], $row->flags ?? [])) ?: '—'));

        $this->actionsColumn($table, static fn (IncentiveProgress $row): array => [
            TableAction::confirm(__('growth.incentive.actions.release'), route('admin.incentive-progress.release', $row), 'review', $row, icon: 'check', variant: 'secondary', confirm: ['title' => __('growth.incentive.confirm.release_title'), 'message' => __('growth.incentive.confirm.release_message'), 'confirm_label' => __('growth.incentive.actions.release')], reloadTable: self::TABLE_ID),
            TableAction::confirm(__('growth.incentive.actions.reject'), route('admin.incentive-progress.reject', $row), 'review', $row, icon: 'x-mark', variant: 'danger', reasonRequired: true, confirm: ['title' => __('growth.incentive.confirm.reject_title'), 'message' => __('growth.incentive.confirm.reject_message'), 'confirm_label' => __('growth.incentive.actions.reject')], reloadTable: self::TABLE_ID),
        ]);
    }
}
