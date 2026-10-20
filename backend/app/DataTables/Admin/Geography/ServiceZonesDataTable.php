<?php

namespace App\DataTables\Admin\Geography;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Geography\ServiceZoneFilterData;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Service zones listing (filters: search, city, status, enabled category, restricted pickup).
 */
final class ServiceZonesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'service-zones-table';

    protected array $defaultOrder = [[0, 'asc']];

    public function __construct(private readonly ServiceZoneRepositoryInterface $zones)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ServiceZone>
     */
    public function query(): Builder
    {
        return $this->zones->datatableQuery(ServiceZoneFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.service_zones.fields.name')),
            $this->column('city', __('admin.service_zones.fields.city')),
            $this->column('status', __('admin.service_zones.fields.status'), orderable: true),
            $this->column('enabled_categories_count', __('admin.service_zones.fields.categories')),
            $this->column('restricted_pickup', __('admin.service_zones.fields.restricted_pickup')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $table->addColumn('city', static fn (ServiceZone $zone): string => (string) $zone->city->name);
        $this->enumBadgeColumn($table, 'status');
        $this->boolColumn($table, 'restricted_pickup');
        $this->actionsColumn($table, fn (ServiceZone $zone): array => [
            TableAction::view(route('admin.service-zones.show', $zone), $zone),
            TableAction::edit(route('admin.service-zones.edit', $zone), $zone),
            TableAction::confirm(
                __('admin.geo.actions.activate'),
                route('admin.service-zones.activate', $zone),
                'activate',
                $zone,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.service_zones.confirm.activate_title'), 'message' => __('admin.service_zones.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.geo.actions.suspend'),
                route('admin.service-zones.suspend', $zone),
                'suspend',
                $zone,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.service_zones.confirm.suspend_title'), 'message' => __('admin.service_zones.confirm.suspend_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.service-zones.destroy', $zone), $zone, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
