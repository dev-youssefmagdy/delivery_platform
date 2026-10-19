<?php

namespace App\DataTables\Admin\Policy;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Policy\PolicyVersionFilterData;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Policy versions listing (filters: title, type, status, published range).
 */
final class PolicyVersionsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'policy-versions-table';

    public function __construct(private readonly PolicyVersionRepositoryInterface $policies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<PolicyVersion>
     */
    public function query(): Builder
    {
        return $this->policies->datatableQuery(PolicyVersionFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('type', __('admin.policy_versions.fields.type'), orderable: true),
            $this->column('title', __('admin.policy_versions.fields.title')),
            $this->column('version', __('admin.policy_versions.fields.version'), orderable: true),
            $this->column('status', __('admin.policy_versions.fields.status'), orderable: true),
            $this->column('published_at', __('admin.policy_versions.fields.published_at'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'title');
        $table->editColumn('type', static fn (Model $row): string => $row instanceof PolicyVersion ? $row->type->label() : '');
        $table->editColumn('version', static fn (Model $row): string => $row instanceof PolicyVersion && $row->version !== null ? 'v'.$row->version : '—');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'published_at');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, fn (PolicyVersion $policy): array => [
            TableAction::view(route('admin.policy-versions.show', $policy), $policy),
            TableAction::edit(route('admin.policy-versions.edit', $policy), $policy),
            TableAction::confirm(
                __('admin.policy_versions.actions.publish'),
                route('admin.policy-versions.publish', $policy),
                'publish',
                $policy,
                icon: 'check-circle',
                variant: 'success',
                reasonRequired: true,
                confirm: ['title' => __('admin.policy_versions.confirm.publish_title'), 'message' => __('admin.policy_versions.confirm.publish_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.policy-versions.destroy', $policy), $policy, self::TABLE_ID),
        ]);
    }
}
