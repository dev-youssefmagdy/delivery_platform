<?php

namespace App\DataTables\Admin\Notification;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Notification\PushCampaignFilterData;
use App\Models\PushCampaign;
use App\Repositories\Contracts\Notification\PushCampaignRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Push campaigns (filters: name, status, audience, created range).
 */
final class PushCampaignsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'push-campaigns-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly PushCampaignRepositoryInterface $campaigns)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<PushCampaign>
     */
    public function query(): Builder
    {
        return $this->campaigns->datatableQuery(PushCampaignFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'push_campaigns.id'),
            $this->column('name', __('admin.push_campaigns.fields.name'), orderable: true, name: 'push_campaigns.name'),
            $this->column('audience', __('admin.push_campaigns.fields.audience'), orderable: true, name: 'push_campaigns.audience'),
            $this->column('status', __('admin.push_campaigns.fields.status'), orderable: true, name: 'push_campaigns.status'),
            $this->column('scheduled_at', __('admin.push_campaigns.fields.scheduled_at'), orderable: true, name: 'push_campaigns.scheduled_at'),
            $this->column('recipients_count', __('admin.push_campaigns.fields.recipients'), orderable: true, name: 'push_campaigns.recipients_count'),
            $this->column('created_by', __('admin.push_campaigns.fields.created_by')),
            $this->column('created_at', __('admin.common.created_at'), orderable: true, name: 'push_campaigns.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'name', 'audience', 'status', 'scheduled_at', 'recipients_count', 'created_by', 'created_at', 'actions']);

        $table->editColumn('name', static fn (Model $row): string => $row instanceof PushCampaign ? '<a href="'.e(route('admin.push-campaigns.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e($row->name).'</a>' : '');
        $this->rawColumn('name');
        $table->editColumn('audience', static fn (Model $row): string => $row instanceof PushCampaign ? e($row->audience->label()) : '');
        $this->enumBadgeColumn($table, 'status');
        $table->editColumn('scheduled_at', fn (Model $row): string => $row instanceof PushCampaign && $row->scheduled_at !== null ? $this->formatDate($row->scheduled_at) : '—');
        $table->addColumn('created_by', static fn (PushCampaign $campaign): string => e($campaign->createdBy->name ?? '—'));
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (PushCampaign $campaign): array => [
            TableAction::view(route('admin.push-campaigns.show', $campaign), $campaign),
            TableAction::edit(route('admin.push-campaigns.edit', $campaign), $campaign),
            TableAction::confirm(
                __('admin.push_campaigns.actions.cancel'),
                route('admin.push-campaigns.cancel', $campaign),
                'cancel',
                $campaign,
                icon: 'x-circle',
                variant: 'danger',
                reasonRequired: true,
                confirm: ['title' => __('admin.push_campaigns.confirm.cancel_title'), 'message' => __('admin.push_campaigns.confirm.cancel_message'), 'confirm_label' => __('admin.push_campaigns.actions.cancel')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
