<?php

namespace App\DataTables\Admin\Catalog;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Catalog\ServiceFamilyFilterData;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Service families listing (filters: search, kind, active).
 */
final class ServiceFamiliesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'service-families-table';

    protected array $defaultOrder = [[3, 'asc']];

    public function __construct(private readonly ServiceFamilyRepositoryInterface $families)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ServiceFamily>
     */
    public function query(): Builder
    {
        return $this->families->datatableQuery(ServiceFamilyFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.catalog.fields.code'), orderable: true),
            $this->column('name', __('admin.service_families.fields.name')),
            $this->column('sort_order', __('admin.catalog.fields.sort_order'), orderable: true),
            $this->column('kind', __('admin.service_families.fields.kind'), orderable: true),
            $this->column('categories_count', __('admin.service_families.fields.categories')),
            $this->column('active', __('admin.catalog.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $table->editColumn('kind', static fn (ServiceFamily $family): string => $family->kind->label());
        $this->boolColumn($table, 'active');
        $this->actionsColumn($table, fn (ServiceFamily $family): array => [
            TableAction::view(route('admin.service-families.show', $family), $family),
            TableAction::edit(route('admin.service-families.edit', $family), $family),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.service-families.activate', $family),
                'activate',
                $family,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.catalog.confirm.activate_title'), 'message' => __('admin.service_families.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.service-families.deactivate', $family),
                'deactivate',
                $family,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.catalog.confirm.deactivate_title'), 'message' => __('admin.service_families.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.service-families.destroy', $family), $family, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
