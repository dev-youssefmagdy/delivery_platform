<?php

namespace App\DataTables\Admin\Provider;

use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Vehicles tab of a provider.
 */
final class ProviderVehiclesDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-vehicles-table';

    protected array $defaultOrder = [[0, 'asc']];

    public function __construct(private readonly VehicleRepositoryInterface $vehicles)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Vehicle>
     */
    public function query(): Builder
    {
        return $this->vehicles->providerTabQuery($this->provider());
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('plate_display', __('admin.vehicles.fields.plate')),
            $this->column('vehicle', __('admin.vehicles.fields.vehicle')),
            $this->column('category', __('admin.vehicles.fields.category')),
            $this->column('status', __('admin.vehicles.fields.status'), orderable: true),
            $this->column('submitted_at', __('admin.vehicles.fields.submitted'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'plate_display', 'vehicle', 'category', 'status', 'submitted_at', 'actions']);
        $table->editColumn('plate_display', static fn (Model $row): string => '<span class="ltr-only font-mono">'.e((string) $row->getAttribute('plate_display')).'</span>');
        $this->rawColumn('plate_display');
        $table->addColumn('vehicle', static fn (Model $row): string => $row instanceof Vehicle ? $row->make.' '.$row->model.' · '.$row->year : '');
        $table->addColumn('category', static fn (Model $row): string => $row instanceof Vehicle ? (string) $row->vehicleCategory->name : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'submitted_at');
        $this->actionsColumn($table, static fn (Vehicle $vehicle): array => [TableAction::view(route('admin.vehicles.show', $vehicle), $vehicle)]);
    }
}
