<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\DataTables\Admin\Catalog\ServiceCategoriesDataTable;
use App\DTOs\Catalog\ServiceCategoryData;
use App\DTOs\Catalog\ServiceFormSchemaData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Catalog\ActivateServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\DeactivateServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\DestroyServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\PublishServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceCategoryVehiclesRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceFormSchemaRequest;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryVersionRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Catalog\CategoryPolicyResolver;
use App\Services\Catalog\Forms\FormSchemaBuilderMapper;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Services\Catalog\ServiceCategoryService;
use App\Services\Catalog\ServiceFormSchemaService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin module for service categories (services.manage): draft configuration, vehicle links, the form-schema
 * builder, publishing, version history and the guarded activate / deactivate actions.
 */
final class ServiceCategoryController extends AdminController
{
    public function __construct(
        private readonly ServiceCategoryService $service,
        private readonly ServiceFormSchemaService $schemas,
        private readonly ServiceCategoryPublisher $publisher,
        private readonly ServiceFamilyRepositoryInterface $families,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly VehicleCategoryRepositoryInterface $vehicleCategories,
        private readonly ServiceCategoryVersionRepositoryInterface $versions,
        private readonly CatalogUsageRepositoryInterface $usage,
        private readonly CategoryPolicyResolver $policies,
    ) {}

    public function index(ServiceCategoriesDataTable $table): mixed
    {
        return $table->render('admin.service-categories.index', ['familyOptions' => $this->familyOptions()]);
    }

    public function create(): View
    {
        return view('admin.service-categories.create', ['familyOptions' => $this->familyOptions()]);
    }

    public function store(StoreServiceCategoryRequest $request): AdminFormResponse
    {
        $category = $this->service->create(ServiceCategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_categories.created'))->redirect(route('admin.service-categories.show', $category));
    }

    public function show(ServiceCategory $serviceCategory): View
    {
        $category = $this->categories->loadDetails($serviceCategory);
        $draft = $this->schemas->draftFor($category);

        return view('admin.service-categories.show', [
            'category' => $category,
            'policy' => $this->policies->resolve($category),
            'draft' => $draft,
            'builderRows' => FormSchemaBuilderMapper::toBuilder($draft->fields),
            'vehicleOptions' => $this->vehicleCategories->allOrdered()->mapWithKeys(fn ($vehicle): array => [$vehicle->id => $vehicle->name.' ('.$vehicle->code.')'])->all(),
            'linkedVehicleIds' => $category->vehicleCategories->pluck('id')->all(),
            'hasUnpublishedChanges' => $this->publisher->hasUnpublishedChanges($category),
            'activeBookings' => $category->active ? $this->usage->activeBookingCount($category) : 0,
        ]);
    }

    public function versions(ServiceCategory $serviceCategory): View
    {
        return view('admin.service-categories.partials.versions', ['category' => $serviceCategory, 'versions' => $this->versions->historyFor($serviceCategory)]);
    }

    public function activity(ServiceCategory $serviceCategory, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($serviceCategory)]);
    }

    public function edit(ServiceCategory $serviceCategory): View
    {
        return view('admin.service-categories.edit', ['category' => $serviceCategory, 'familyOptions' => $this->familyOptions()]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->service->update($serviceCategory, ServiceCategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_categories.updated'))->redirect(route('admin.service-categories.show', $serviceCategory));
    }

    public function updateVehicleCategories(UpdateServiceCategoryVehiclesRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->service->syncVehicleCategories($serviceCategory, $request->vehicleCategoryIds(), $this->actor());

        return $this->formResponse(__('admin.service_categories.vehicles_saved'))->reload();
    }

    public function updateFormSchema(UpdateServiceFormSchemaRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->schemas->saveDraft($serviceCategory, ServiceFormSchemaData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_categories.schema_saved'))->reload();
    }

    public function publish(PublishServiceCategoryRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $version = $this->publisher->publish($serviceCategory, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.service_categories.published', ['version' => $version->version]))->reload();
    }

    public function activate(ActivateServiceCategoryRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->service->activate($serviceCategory, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.activated'), $serviceCategory);
    }

    public function deactivate(DeactivateServiceCategoryRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->service->deactivate(
            $serviceCategory,
            (string) $request->reason(),
            $request->boolean('confirm_active_bookings'),
            $this->actor(),
        );

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.deactivated'), $serviceCategory);
    }

    public function destroy(DestroyServiceCategoryRequest $request, ServiceCategory $serviceCategory): AdminFormResponse
    {
        $this->service->delete($serviceCategory, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.service_categories.deleted'))->reloadTable(ServiceCategoriesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.service-categories.index')) : $response;
    }

    /**
     * @return array<int, string>
     */
    private function familyOptions(): array
    {
        return $this->families->allOrdered()->mapWithKeys(fn ($family): array => [$family->id => $family->name])->all();
    }

    private function afterStateChange(bool $fromShow, string $message, ServiceCategory $category): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(ServiceCategoriesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect(route('admin.service-categories.show', $category)) : $response;
    }
}
