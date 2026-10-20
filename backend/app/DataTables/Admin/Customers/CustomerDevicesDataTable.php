<?php

namespace App\DataTables\Admin\Customers;

use App\Models\UserDevice;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Devices a customer signed in from. Push tokens are never selected or shown.
 */
final class CustomerDevicesDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-devices-table';

    protected array $defaultOrder = [[5, 'desc']];

    public function __construct(private readonly UserDeviceRepositoryInterface $devices)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<UserDevice>
     */
    public function query(): Builder
    {
        return $this->devices->datatableQuery($this->customer());
    }

    protected function columns(): array
    {
        return [
            $this->column('app', __('admin.customers.devices.app'), orderable: true),
            $this->column('platform', __('admin.customers.devices.platform'), orderable: true),
            $this->column('device_id', __('admin.customers.devices.device_id')),
            $this->column('app_version', __('admin.customers.devices.app_version')),
            $this->column('locale', __('admin.customers.devices.locale')),
            $this->column('last_active_at', __('admin.customers.devices.last_active'), orderable: true),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['app', 'platform', 'device_id', 'app_version', 'locale', 'last_active_at']);
        $table->editColumn('app', static fn (Model $row): string => $row instanceof UserDevice ? e($row->app->label()) : '');
        $table->editColumn('platform', static fn (Model $row): string => $row instanceof UserDevice ? e($row->platform->label()) : '');
        $table->editColumn('device_id', static fn (Model $row): string => '<span class="ltr-only">'.e((string) $row->getAttribute('device_id')).'</span>');
        $table->editColumn('app_version', static fn (Model $row): string => (string) ($row->getAttribute('app_version') ?? '—'));
        $table->editColumn('locale', static fn (Model $row): string => (string) ($row->getAttribute('locale') ?? '—'));
        $this->dateColumn($table, 'last_active_at');
        $this->rawColumn('device_id');
    }
}
