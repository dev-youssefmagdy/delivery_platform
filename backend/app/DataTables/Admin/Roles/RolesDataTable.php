<?php

namespace App\DataTables\Admin\Roles;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Admin\RoleFilterData;
use App\Models\Role;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Roles listing (filters: name search, system roles).
 */
final class RolesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'roles-table';

    public function __construct(private readonly RoleRepositoryInterface $roles)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Role>
     */
    public function query(): Builder
    {
        return $this->roles->datatableQuery(RoleFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('label', __('admin.roles.fields.label')),
            $this->column('name', __('admin.roles.fields.name'), orderable: true, searchable: true),
            $this->column('is_system', __('admin.roles.fields.type'), orderable: true),
            $this->column('permissions_count', __('admin.roles.fields.permissions')),
            $this->column('admins_count', __('admin.roles.fields.admins')),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'label');
        $this->dateColumn($table, 'created_at');

        $table->editColumn('is_system', static fn (Model $row): string => Blade::renderComponent(new Badge(
            label: $row->getAttribute('is_system') ? __('admin.roles.type.system') : __('admin.roles.type.custom'),
            color: $row->getAttribute('is_system') ? 'info' : 'neutral',
        )));
        $table->rawColumns(['is_system']);

        $this->actionsColumn($table, fn (Role $role): array => [
            TableAction::view(route('admin.roles.show', $role), $role),
            TableAction::edit(route('admin.roles.edit', $role), $role),
            TableAction::delete(route('admin.roles.destroy', $role), $role, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
