<?php

namespace App\Http\Controllers\Admin\Roles;

use App\DataTables\Admin\Roles\RolesDataTable;
use App\DTOs\Admin\RoleData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Roles\DestroyRoleRequest;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Models\Role;
use App\Services\Admin\RoleService;
use App\Services\Audit\AuditTrailService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Roles (roles.view / roles.manage) with the grouped permission matrix.
 */
final class RoleController extends AdminController
{
    public function __construct(private readonly RoleService $service) {}

    public function index(RolesDataTable $table): mixed
    {
        return $table->render('admin.roles.index');
    }

    public function create(): View
    {
        return view('admin.roles.create', ['matrix' => $this->service->matrix(null)]);
    }

    public function store(StoreRoleRequest $request): AdminFormResponse
    {
        $role = $this->service->create(RoleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.roles.created'))->redirect(route('admin.roles.show', $role));
    }

    public function show(Role $role): View
    {
        return view('admin.roles.show', ['role' => $role, 'matrix' => $this->service->matrix($role)]);
    }

    public function activity(Role $role, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($role)]);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', ['role' => $role, 'matrix' => $this->service->matrix($role)]);
    }

    public function update(UpdateRoleRequest $request, Role $role): AdminFormResponse
    {
        $this->service->update($role, RoleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.roles.updated'))->redirect(route('admin.roles.show', $role));
    }

    public function destroy(DestroyRoleRequest $request, Role $role): AdminFormResponse
    {
        $this->service->delete($role, $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.roles.deleted'))->reloadTable(RolesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.roles.index')) : $response;
    }
}
