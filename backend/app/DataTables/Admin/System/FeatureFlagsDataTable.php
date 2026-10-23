<?php

namespace App\DataTables\Admin\System;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\System\FeatureFlagFilterData;
use App\Models\FeatureFlag;
use App\Repositories\Contracts\System\FeatureFlagRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Feature flags (filters: key, enabled).
 */
final class FeatureFlagsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'feature-flags-table';

    protected array $defaultOrder = [[0, 'asc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly FeatureFlagRepositoryInterface $flags)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<FeatureFlag>
     */
    public function query(): Builder
    {
        return $this->flags->datatableQuery(FeatureFlagFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('key', __('system.flags.fields.key'), orderable: true, name: 'feature_flags.key'),
            $this->column('enabled', __('system.flags.fields.enabled'), orderable: true, name: 'feature_flags.enabled'),
            $this->column('apps', __('system.flags.fields.apps')),
            $this->column('cities', __('system.flags.fields.cities')),
            $this->column('rollout_percent', __('system.flags.fields.rollout'), orderable: true, name: 'feature_flags.rollout_percent'),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true, name: 'feature_flags.updated_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['key', 'enabled', 'apps', 'cities', 'rollout_percent', 'updated_at', 'actions']);

        $table->editColumn('key', static fn (FeatureFlag $row): string => e($row->key->label()).' <code class="text-xs text-slate-500">'.e($row->key->value).'</code>');
        $this->rawColumn('key');
        $this->boolColumn($table, 'enabled');
        $table->addColumn('apps', static fn (FeatureFlag $row): string => e($row->apps === null || $row->apps === [] ? __('system.flags.everyone') : implode(', ', array_map(static fn (string $app): string => (string) __('system.enums.app.'.$app), $row->apps))));
        $table->addColumn('cities', static fn (FeatureFlag $row): string => e($row->city_ids === null || $row->city_ids === [] ? __('system.flags.all_cities') : __('system.flags.n_cities', ['count' => count($row->city_ids)])));
        $table->editColumn('rollout_percent', static fn (FeatureFlag $row): string => $row->rollout_percent.'%');
        $this->dateColumn($table, 'updated_at');

        $this->actionsColumn($table, static fn (FeatureFlag $row): array => [TableAction::edit(route('admin.feature-flags.edit', $row), $row)]);
    }
}
