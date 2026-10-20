<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Provider\VehicleFilterData;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Vehicles listing (Appendix C filters: plate, vehicle category, status, provider) with approve / reject on vehicles
 * under review.
 */
final class VehiclesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'vehicles-table';

    protected bool $globalSearch = false;

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
        return $this->vehicles->datatableQuery(VehicleFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('plate_display', __('admin.vehicles.fields.plate'), orderable: true),
            $this->column('vehicle', __('admin.vehicles.fields.vehicle')),
            $this->column('category', __('admin.vehicles.fields.category')),
            $this->column('provider', __('admin.vehicles.fields.provider')),
            $this->column('status', __('admin.vehicles.fields.status'), orderable: true),
            $this->column('submitted_at', __('admin.vehicles.fields.submitted'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'uuid', 'plate_display', 'vehicle', 'category', 'provider', 'status', 'submitted_at', 'actions']);
        $table->editColumn('plate_display', static fn (Model $row): string => '<span class="ltr-only font-mono">'.e((string) $row->getAttribute('plate_display')).'</span>');
        $this->rawColumn('plate_display');
        $table->addColumn('vehicle', static fn (Model $row): string => $row instanceof Vehicle ? $row->make.' '.$row->model.' · '.$row->year : '');
        $table->addColumn('category', static fn (Model $row): string => $row instanceof Vehicle ? (string) $row->vehicleCategory->name : '');
        $table->addColumn('provider', static fn (Model $row): string => $row instanceof Vehicle ? ProvidersDataTable::displayName($row->providerProfile) : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'submitted_at');

        $this->actionsColumn($table, static fn (Vehicle $vehicle): array => [
            TableAction::view(route('admin.vehicles.show', $vehicle), $vehicle),
            TableAction::confirm(
                __('admin.vehicles.actions.approve'),
                route('admin.vehicles.approve', $vehicle),
                'approve',
                $vehicle,
                icon: 'check-circle',
                variant: 'success',
                confirm: ['title' => __('admin.vehicles.confirm.approve_title'), 'message' => __('admin.vehicles.confirm.approve_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.vehicles.actions.reject'),
                route('admin.vehicles.reject', $vehicle),
                'reject',
                $vehicle,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.vehicles.confirm.reject_title'), 'message' => __('admin.vehicles.confirm.reject_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
