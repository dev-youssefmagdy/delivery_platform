<?php

namespace App\DataTables\Admin\Geography;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Geography\CityFilterData;
use App\Models\City;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Cities listing (filters: search, country, active).
 */
final class CitiesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'cities-table';

    protected array $defaultOrder = [[0, 'asc']];

    public function __construct(private readonly CityRepositoryInterface $cities)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<City>
     */
    public function query(): Builder
    {
        return $this->cities->datatableQuery(CityFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.cities.fields.name')),
            $this->column('country', __('admin.cities.fields.country')),
            $this->column('timezone', __('admin.cities.fields.timezone')),
            $this->column('zones_count', __('admin.cities.fields.zones')),
            $this->column('active', __('admin.geo.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $table->editColumn('country', static fn (City $city): string => (string) $city->country->name);
        $this->boolColumn($table, 'active');
        $this->actionsColumn($table, fn (City $city): array => [
            TableAction::view(route('admin.cities.show', $city), $city),
            TableAction::edit(route('admin.cities.edit', $city), $city),
            TableAction::confirm(
                __('admin.geo.actions.activate'),
                route('admin.cities.activate', $city),
                'activate',
                $city,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.geo.confirm.activate_title'), 'message' => __('admin.cities.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.geo.actions.deactivate'),
                route('admin.cities.deactivate', $city),
                'deactivate',
                $city,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.geo.confirm.deactivate_title'), 'message' => __('admin.cities.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.cities.destroy', $city), $city, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
