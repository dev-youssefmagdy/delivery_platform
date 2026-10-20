<?php

namespace App\DataTables\Admin\Catalog;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Catalog\VehicleCategoryFilterData;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Vehicle categories listing (filters: search, active).
 */
final class VehicleCategoriesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'vehicle-categories-table';

    protected array $defaultOrder = [[3, 'asc']];

    public function __construct(private readonly VehicleCategoryRepositoryInterface $vehicleCategories)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<VehicleCategory>
     */
    public function query(): Builder
    {
        return $this->vehicleCategories->datatableQuery(VehicleCategoryFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.catalog.fields.code'), orderable: true),
            $this->column('name', __('admin.vehicle_categories.fields.name')),
            $this->column('sort_order', __('admin.catalog.fields.sort_order'), orderable: true),
            $this->column('passenger_capacity', __('admin.vehicle_categories.fields.passenger_capacity'), orderable: true),
            $this->column('max_payload_kg', __('admin.vehicle_categories.fields.max_payload_kg'), orderable: true),
            $this->column('service_categories_count', __('admin.vehicle_categories.fields.service_categories')),
            $this->column('active', __('admin.catalog.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $this->boolColumn($table, 'active');
        $this->actionsColumn($table, fn (VehicleCategory $vehicle): array => [
            TableAction::view(route('admin.vehicle-categories.show', $vehicle), $vehicle),
            TableAction::edit(route('admin.vehicle-categories.edit', $vehicle), $vehicle),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.vehicle-categories.activate', $vehicle),
                'activate',
                $vehicle,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.catalog.confirm.activate_title'), 'message' => __('admin.vehicle_categories.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.vehicle-categories.deactivate', $vehicle),
                'deactivate',
                $vehicle,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.catalog.confirm.deactivate_title'), 'message' => __('admin.vehicle_categories.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.vehicle-categories.destroy', $vehicle), $vehicle, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
