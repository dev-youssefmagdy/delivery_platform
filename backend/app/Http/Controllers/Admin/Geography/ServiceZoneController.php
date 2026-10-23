<?php

namespace App\Http\Controllers\Admin\Geography;

use App\DataTables\Admin\Geography\ServiceZonesDataTable;
use App\DTOs\Geography\ServiceZoneData;
use App\DTOs\Geography\ZoneAvailabilityData;
use App\DTOs\Geography\ZoneSaveResult;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Geography\ActivateServiceZoneRequest;
use App\Http\Requests\Admin\Geography\CheckZoneOverlapRequest;
use App\Http\Requests\Admin\Geography\DestroyServiceZoneRequest;
use App\Http\Requests\Admin\Geography\StoreServiceZoneRequest;
use App\Http\Requests\Admin\Geography\SuspendServiceZoneRequest;
use App\Http\Requests\Admin\Geography\UpdateServiceZoneRequest;
use App\Http\Requests\Admin\Geography\UpdateZoneAvailabilityRequest;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Geography\ServiceZoneService;
use App\Support\Http\AdminFormResponse;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Admin CRUD for service zones (service_areas.manage), the availability matrix and the live overlap check.
 */
final class ServiceZoneController extends AdminController
{
    public function __construct(private readonly ServiceZoneService $service) {}

    public function index(ServiceZonesDataTable $table): mixed
    {
        return $table->render('admin.service-zones.index');
    }

    public function create(): View
    {
        return view('admin.service-zones.create');
    }

    public function store(StoreServiceZoneRequest $request): AdminFormResponse
    {
        $result = $this->service->create(ServiceZoneData::fromRequest($request), $this->actor());

        return $this->formResponse($this->message(__('admin.service_zones.created'), $result))->redirect(route('admin.service-zones.show', $result->zone));
    }

    public function show(
        ServiceZone $serviceZone,
        ServiceZoneRepositoryInterface $zones,
        ServiceCategoryRepositoryInterface $categories,
        ZoneServiceCategoryRepositoryInterface $links,
        BookingRepositoryInterface $bookings,
    ): View {
        $serviceZone->load('city.country');

        return view('admin.service-zones.show', [
            'zone' => $serviceZone,
            'areaKm2' => $zones->areaSquareMeters($serviceZone) / 1_000_000,
            'overlaps' => $zones->overlapping($serviceZone->polygon, $serviceZone->id),
            'categories' => $categories->allOrdered(),
            'links' => $links->forZone($serviceZone->id),
            'activeBookings' => $bookings->inFlightCountForZone($serviceZone->id),
        ]);
    }

    public function activity(ServiceZone $serviceZone, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($serviceZone)]);
    }

    public function edit(ServiceZone $serviceZone): View
    {
        return view('admin.service-zones.edit', ['zone' => $serviceZone->load('city')]);
    }

    public function update(UpdateServiceZoneRequest $request, ServiceZone $serviceZone): AdminFormResponse
    {
        $result = $this->service->update($serviceZone, ServiceZoneData::fromRequest($request), $this->actor());

        return $this->formResponse($this->message(__('admin.service_zones.updated'), $result))->redirect(route('admin.service-zones.show', $serviceZone));
    }

    public function availability(UpdateZoneAvailabilityRequest $request, ServiceZone $serviceZone): AdminFormResponse
    {
        $this->service->updateAvailability($serviceZone, ZoneAvailabilityData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.service_zones.availability_saved'))->redirect(route('admin.service-zones.show', $serviceZone).'#availability');
    }

    public function activate(ActivateServiceZoneRequest $request, ServiceZone $serviceZone): AdminFormResponse
    {
        $this->service->activate($serviceZone, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.service_zones.activated'), route('admin.service-zones.show', $serviceZone));
    }

    public function suspend(SuspendServiceZoneRequest $request, ServiceZone $serviceZone): AdminFormResponse
    {
        $this->service->suspend($serviceZone, (string) $request->reason(), $this->actor(), $request->boolean('confirm_active_bookings'));

        return $this->afterStateChange($request->boolean('from_show'), __('admin.service_zones.suspended'), route('admin.service-zones.show', $serviceZone));
    }

    public function destroy(DestroyServiceZoneRequest $request, ServiceZone $serviceZone): AdminFormResponse
    {
        $this->service->delete($serviceZone, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.service_zones.deleted'))->reloadTable(ServiceZonesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.service-zones.index')) : $response;
    }

    public function overlaps(CheckZoneOverlapRequest $request): JsonResponse
    {
        $polygon = $request->polygon();
        $zones = $polygon === null ? collect() : $this->service->overlaps($polygon, $request->zoneId());

        return ApiResponse::success(data: [
            'overlaps' => $zones->map(static fn (ServiceZone $zone): array => ['id' => $zone->id, 'name' => $zone->name, 'city' => $zone->city->name])->values()->all(),
        ]);
    }

    private function message(string $base, ZoneSaveResult $result): string
    {
        if ($result->overlaps->isEmpty()) {
            return $base;
        }

        return $base.' '.__('admin.service_zones.overlap_warning', ['zones' => $result->overlaps->map(static fn (ServiceZone $zone): string => (string) $zone->name)->implode(__('admin.list_separator'))]);
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(ServiceZonesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
