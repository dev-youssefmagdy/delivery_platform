<?php

namespace App\Http\Controllers\Admin\Geography;

use App\DataTables\Admin\Geography\CitiesDataTable;
use App\DTOs\Geography\CityData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Geography\ActivateCityRequest;
use App\Http\Requests\Admin\Geography\DeactivateCityRequest;
use App\Http\Requests\Admin\Geography\DestroyCityRequest;
use App\Http\Requests\Admin\Geography\StoreCityRequest;
use App\Http\Requests\Admin\Geography\UpdateCityRequest;
use App\Models\City;
use App\Services\Audit\AuditTrailService;
use App\Services\Geography\CityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin CRUD for cities (service_areas.manage). Authorization: route `can:` + FormRequests.
 */
final class CityController extends AdminController
{
    public function __construct(private readonly CityService $service) {}

    public function index(CitiesDataTable $table): mixed
    {
        return $table->render('admin.cities.index');
    }

    public function create(): View
    {
        return view('admin.cities.create');
    }

    public function store(StoreCityRequest $request): AdminFormResponse
    {
        $city = $this->service->create(CityData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.cities.created'))->redirect(route('admin.cities.show', $city));
    }

    public function show(City $city): View
    {
        return view('admin.cities.show', ['city' => $city->load('country')->loadCount('zones')]);
    }

    public function activity(City $city, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($city)]);
    }

    public function edit(City $city): View
    {
        return view('admin.cities.edit', ['city' => $city->load('country')]);
    }

    public function update(UpdateCityRequest $request, City $city): AdminFormResponse
    {
        $this->service->update($city, CityData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.cities.updated'))->redirect(route('admin.cities.show', $city));
    }

    public function activate(ActivateCityRequest $request, City $city): AdminFormResponse
    {
        $this->service->activate($city, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.geo.activated'), route('admin.cities.show', $city));
    }

    public function deactivate(DeactivateCityRequest $request, City $city): AdminFormResponse
    {
        $this->service->deactivate($city, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.geo.deactivated'), route('admin.cities.show', $city));
    }

    public function destroy(DestroyCityRequest $request, City $city): AdminFormResponse
    {
        $this->service->delete($city, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.cities.deleted'))->reloadTable(CitiesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.cities.index')) : $response;
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(CitiesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
