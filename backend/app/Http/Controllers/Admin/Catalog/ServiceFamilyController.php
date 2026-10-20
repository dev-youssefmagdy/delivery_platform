<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\DataTables\Admin\Catalog\ServiceFamiliesDataTable;
use App\DTOs\Catalog\ServiceFamilyData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Catalog\ActivateServiceFamilyRequest;
use App\Http\Requests\Admin\Catalog\DeactivateServiceFamilyRequest;
use App\Http\Requests\Admin\Catalog\DestroyServiceFamilyRequest;
use App\Http\Requests\Admin\Catalog\StoreServiceFamilyRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceFamilyRequest;
use App\Models\ServiceFamily;
use App\Services\Audit\AuditTrailService;
use App\Services\Catalog\ServiceFamilyService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin CRUD for service families (services.manage). Authorization: route `can:` + FormRequests.
 */
final class ServiceFamilyController extends AdminController
{
    public function __construct(private readonly ServiceFamilyService $service) {}

    public function index(ServiceFamiliesDataTable $table): mixed
    {
        return $table->render('admin.service-families.index');
    }

    public function create(): View
    {
        return view('admin.service-families.create');
    }

    public function store(StoreServiceFamilyRequest $request): AdminFormResponse
    {
        $family = $this->service->create(ServiceFamilyData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_families.created'))->redirect(route('admin.service-families.show', $family));
    }

    public function show(ServiceFamily $serviceFamily): View
    {
        return view('admin.service-families.show', ['family' => $serviceFamily->loadCount('categories')]);
    }

    public function activity(ServiceFamily $serviceFamily, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($serviceFamily)]);
    }

    public function edit(ServiceFamily $serviceFamily): View
    {
        return view('admin.service-families.edit', ['family' => $serviceFamily]);
    }

    public function update(UpdateServiceFamilyRequest $request, ServiceFamily $serviceFamily): AdminFormResponse
    {
        $this->service->update($serviceFamily, ServiceFamilyData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_families.updated'))->redirect(route('admin.service-families.show', $serviceFamily));
    }

    public function activate(ActivateServiceFamilyRequest $request, ServiceFamily $serviceFamily): AdminFormResponse
    {
        $this->service->activate($serviceFamily, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.activated'), route('admin.service-families.show', $serviceFamily));
    }

    public function deactivate(DeactivateServiceFamilyRequest $request, ServiceFamily $serviceFamily): AdminFormResponse
    {
        $this->service->deactivate($serviceFamily, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.deactivated'), route('admin.service-families.show', $serviceFamily));
    }

    public function destroy(DestroyServiceFamilyRequest $request, ServiceFamily $serviceFamily): AdminFormResponse
    {
        $this->service->delete($serviceFamily, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.service_families.deleted'))->reloadTable(ServiceFamiliesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.service-families.index')) : $response;
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(ServiceFamiliesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
