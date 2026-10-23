<?php

namespace App\DataTables\Admin\System;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\System\AppVersionFilterData;
use App\Models\AppVersion;
use App\Repositories\Contracts\System\AppVersionRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * App versions (filters: app, platform, force update).
 */
final class AppVersionsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'app-versions-table';

    protected array $defaultOrder = [[0, 'asc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly AppVersionRepositoryInterface $versions)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<AppVersion>
     */
    public function query(): Builder
    {
        return $this->versions->datatableQuery(AppVersionFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('app', __('system.app_versions.fields.app'), orderable: true, name: 'app_versions.app'),
            $this->column('platform', __('system.app_versions.fields.platform'), orderable: true, name: 'app_versions.platform'),
            $this->column('min_version', __('system.app_versions.fields.min_version')),
            $this->column('latest_version', __('system.app_versions.fields.latest_version')),
            $this->column('force_update', __('system.app_versions.fields.force_update'), orderable: true, name: 'app_versions.force_update'),
            $this->column('store_url', __('system.app_versions.fields.store_url')),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true, name: 'app_versions.updated_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['app', 'platform', 'min_version', 'latest_version', 'force_update', 'store_url', 'updated_at', 'actions']);

        $this->enumBadgeColumn($table, 'app');
        $this->enumBadgeColumn($table, 'platform');
        $table->editColumn('min_version', static fn (AppVersion $row): string => '<span dir="ltr">'.e($row->min_version).'</span>');
        $table->editColumn('latest_version', static fn (AppVersion $row): string => '<span dir="ltr">'.e($row->latest_version ?? '—').'</span>');
        $table->editColumn('store_url', static fn (AppVersion $row): string => $row->store_url === null ? '—' : '<a dir="ltr" class="text-brand-700 hover:underline" rel="noopener noreferrer" target="_blank" href="'.e($row->store_url).'">'.e(parse_url($row->store_url, PHP_URL_HOST) ?: '—').'</a>');
        $this->rawColumn('min_version');
        $this->rawColumn('latest_version');
        $this->rawColumn('store_url');
        $this->boolColumn($table, 'force_update');
        $this->dateColumn($table, 'updated_at');

        $this->actionsColumn($table, static fn (AppVersion $row): array => [TableAction::edit(route('admin.app-versions.edit', $row), $row)]);
    }
}
