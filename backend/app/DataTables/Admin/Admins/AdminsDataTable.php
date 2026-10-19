<?php

namespace App\DataTables\Admin\Admins;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Admin\AdminUserFilterData;
use App\Models\Admin;
use App\Models\Role;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Admin users listing (filters: name/email, role, status, MFA enabled, created range).
 */
final class AdminsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'admins-table';

    public function __construct(private readonly AdminRepositoryInterface $admins)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Admin>
     */
    public function query(): Builder
    {
        return $this->admins->datatableQuery(AdminUserFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.admins.fields.name'), orderable: true, searchable: true),
            $this->column('roles', __('admin.admins.fields.roles')),
            $this->column('status', __('admin.admins.fields.status'), orderable: true),
            $this->column('mfa_enabled_at', __('admin.admins.fields.mfa'), orderable: true),
            $this->column('last_login_at', __('admin.admins.fields.last_login'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->userColumn($table, 'name', static fn (Admin $admin): array => [$admin->name, $admin->email]);
        $this->enumBadgeColumn($table, 'status');
        $this->boolColumn($table, 'mfa_enabled_at');
        $this->dateColumn($table, 'last_login_at');
        $this->dateColumn($table, 'created_at');

        $table->addColumn('roles', static fn (Model $row): string => $row instanceof Admin
            ? $row->roles->map(static fn (Role $role): string => Blade::renderComponent(new Badge(label: $role->displayName(), color: 'info', dot: false)))->implode(' ')
            : '');
        $table->rawColumns(['roles']);

        $this->actionsColumn($table, fn (Admin $admin): array => [
            TableAction::view(route('admin.admins.show', $admin), $admin),
            TableAction::edit(route('admin.admins.edit', $admin), $admin),
            TableAction::confirm(
                __('admin.admins.actions.suspend'),
                route('admin.admins.suspend', $admin),
                'suspend',
                $admin,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.admins.confirm.suspend_title'), 'message' => __('admin.admins.confirm.suspend_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.admins.actions.reactivate'),
                route('admin.admins.reactivate', $admin),
                'reactivate',
                $admin,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.admins.confirm.reactivate_title'), 'message' => __('admin.admins.confirm.reactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.admins.destroy', $admin), $admin, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
