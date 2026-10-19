<?php

namespace App\Http\Controllers\Admin\Markets;

use App\DataTables\Admin\Markets\CountriesDataTable;
use App\DTOs\Markets\CountryData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Markets\DestroyCountryRequest;
use App\Http\Requests\Admin\Markets\StoreCountryRequest;
use App\Http\Requests\Admin\Markets\UpdateCountryRequest;
use App\Models\Country;
use App\Services\Audit\AuditTrailService;
use App\Services\Markets\CountryService;
use App\Support\Http\AdminFormResponse;
use DateTimeZone;
use Illuminate\Contracts\View\View;

/**
 * Admin CRUD for countries (markets.manage).
 */
final class CountryController extends AdminController
{
    public function __construct(private readonly CountryService $service) {}

    public function index(CountriesDataTable $table): mixed
    {
        return $table->render('admin.countries.index', ['currencyOptions' => $this->service->currencyOptions()]);
    }

    public function create(): View
    {
        return view('admin.countries.create', $this->formData());
    }

    public function store(StoreCountryRequest $request): AdminFormResponse
    {
        $country = $this->service->create(CountryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.countries.created'))->redirect(route('admin.countries.show', $country));
    }

    public function show(Country $country): View
    {
        return view('admin.countries.show', ['country' => $country->loadMissing('currency')]);
    }

    public function activity(Country $country, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($country)]);
    }

    public function edit(Country $country): View
    {
        return view('admin.countries.edit', ['country' => $country] + $this->formData());
    }

    public function update(UpdateCountryRequest $request, Country $country): AdminFormResponse
    {
        $this->service->update($country, CountryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.countries.updated'))->redirect(route('admin.countries.show', $country));
    }

    public function destroy(DestroyCountryRequest $request, Country $country): AdminFormResponse
    {
        $this->service->delete($country, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.countries.deleted'))->reloadTable(CountriesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.countries.index')) : $response;
    }

    /**
     * @return array{currencyOptions: array<string, string>, timezoneOptions: array<string, string>}
     */
    private function formData(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return [
            'currencyOptions' => $this->service->currencyOptions(),
            'timezoneOptions' => array_combine($timezones, $timezones),
        ];
    }
}
