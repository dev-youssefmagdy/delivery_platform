<?php

namespace App\Http\Controllers\Admin\Admins;

use App\DataTables\Admin\Admins\AdminsDataTable;
use App\DTOs\Admin\AdminUserData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Admins\DestroyAdminRequest;
use App\Http\Requests\Admin\Admins\ReactivateAdminRequest;
use App\Http\Requests\Admin\Admins\ResetAdminMfaRequest;
use App\Http\Requests\Admin\Admins\StoreAdminRequest;
use App\Http\Requests\Admin\Admins\SuspendAdminRequest;
use App\Http\Requests\Admin\Admins\UpdateAdminRequest;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Services\Admin\AdminUserService;
use App\Services\Audit\AuditTrailService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin users (admins.view / admins.manage): CRUD, role assignment, suspend/reactivate, MFA reset.
 */
final class AdminUserController extends AdminController
{
    public function __construct(
        private readonly AdminUserService $service,
        private readonly RoleRepositoryInterface $roles,
    ) {}

    public function index(AdminsDataTable $table): mixed
    {
        return $table->render('admin.admins.index', ['roleOptions' => $this->roleOptions()]);
    }

    public function create(): View
    {
        return view('admin.admins.create', ['roleOptions' => $this->roleOptions()]);
    }

    public function store(StoreAdminRequest $request): AdminFormResponse
    {
        $admin = $this->service->create(AdminUserData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.admins.created'))->redirect(route('admin.admins.show', $admin));
    }

    public function show(Admin $admin): View
    {
        return view('admin.admins.show', ['details' => $this->service->details($admin)]);
    }

    public function activity(Admin $admin, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($admin)]);
    }

    public function edit(Admin $admin): View
    {
        return view('admin.admins.edit', ['details' => $this->service->details($admin), 'roleOptions' => $this->roleOptions()]);
    }

    public function update(UpdateAdminRequest $request, Admin $admin): AdminFormResponse
    {
        $this->service->update($admin, AdminUserData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.admins.updated'))->redirect(route('admin.admins.show', $admin));
    }

    public function suspend(SuspendAdminRequest $request, Admin $admin): AdminFormResponse
    {
        $this->service->suspend($admin, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.admins.suspended'));
    }

    public function reactivate(ReactivateAdminRequest $request, Admin $admin): AdminFormResponse
    {
        $this->service->reactivate($admin, $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.admins.reactivated'));
    }

    public function resetMfa(ResetAdminMfaRequest $request, Admin $admin): AdminFormResponse
    {
        $this->service->resetMfa($admin, (string) $request->reason(), $this->actor());

        return $this->afterAction($request->boolean('from_show'), __('admin.admins.mfa_reset'));
    }

    public function destroy(DestroyAdminRequest $request, Admin $admin): AdminFormResponse
    {
        $this->service->delete($admin, $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.admins.deleted'))->reloadTable(AdminsDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.admins.index')) : $response;
    }

    /**
     * @return array<string, string> role name => display name
     */
    private function roleOptions(): array
    {
        return $this->roles->allOrdered()->mapWithKeys(static fn ($role): array => [$role->name => $role->displayName()])->all();
    }

    private function afterAction(bool $fromShow, string $message): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(AdminsDataTable::TABLE_ID);

        return $fromShow ? $response->reload() : $response;
    }
}
