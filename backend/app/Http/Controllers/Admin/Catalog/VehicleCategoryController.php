<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\DataTables\Admin\Catalog\VehicleCategoriesDataTable;
use App\DTOs\Catalog\VehicleCategoryData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Catalog\ActivateVehicleCategoryRequest;
use App\Http\Requests\Admin\Catalog\DeactivateVehicleCategoryRequest;
use App\Http\Requests\Admin\Catalog\DestroyVehicleCategoryRequest;
use App\Http\Requests\Admin\Catalog\StoreVehicleCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateVehicleCategoryRequest;
use App\Models\VehicleCategory;
use App\Services\Audit\AuditTrailService;
use App\Services\Catalog\VehicleCategoryService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin CRUD for vehicle categories (vehicle_categories.manage).
 */
final class VehicleCategoryController extends AdminController
{
    public function __construct(private readonly VehicleCategoryService $service) {}

    public function index(VehicleCategoriesDataTable $table): mixed
    {
        return $table->render('admin.vehicle-categories.index');
    }

    public function create(): View
    {
        return view('admin.vehicle-categories.create');
    }

    public function store(StoreVehicleCategoryRequest $request): AdminFormResponse
    {
        $vehicle = $this->service->create(VehicleCategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.vehicle_categories.created'))->redirect(route('admin.vehicle-categories.show', $vehicle));
    }

    public function show(VehicleCategory $vehicleCategory): View
    {
        return view('admin.vehicle-categories.show', ['vehicle' => $vehicleCategory->loadCount('serviceCategories')]);
    }

    public function activity(VehicleCategory $vehicleCategory, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($vehicleCategory)]);
    }

    public function edit(VehicleCategory $vehicleCategory): View
    {
        return view('admin.vehicle-categories.edit', ['vehicle' => $vehicleCategory]);
    }

    public function update(UpdateVehicleCategoryRequest $request, VehicleCategory $vehicleCategory): AdminFormResponse
    {
        $this->service->update($vehicleCategory, VehicleCategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.vehicle_categories.updated'))->redirect(route('admin.vehicle-categories.show', $vehicleCategory));
    }

    public function activate(ActivateVehicleCategoryRequest $request, VehicleCategory $vehicleCategory): AdminFormResponse
    {
        $this->service->activate($vehicleCategory, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.activated'), route('admin.vehicle-categories.show', $vehicleCategory));
    }

    public function deactivate(DeactivateVehicleCategoryRequest $request, VehicleCategory $vehicleCategory): AdminFormResponse
    {
        $this->service->deactivate($vehicleCategory, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.deactivated'), route('admin.vehicle-categories.show', $vehicleCategory));
    }

    public function destroy(DestroyVehicleCategoryRequest $request, VehicleCategory $vehicleCategory): AdminFormResponse
    {
        $this->service->delete($vehicleCategory, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.vehicle_categories.deleted'))->reloadTable(VehicleCategoriesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.vehicle-categories.index')) : $response;
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(VehicleCategoriesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
