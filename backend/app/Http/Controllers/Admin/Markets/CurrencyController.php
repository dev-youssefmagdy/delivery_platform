<?php

namespace App\Http\Controllers\Admin\Markets;

use App\DataTables\Admin\Markets\CurrenciesDataTable;
use App\DTOs\Markets\CurrencyData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Markets\DestroyCurrencyRequest;
use App\Http\Requests\Admin\Markets\StoreCurrencyRequest;
use App\Http\Requests\Admin\Markets\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\Audit\AuditTrailService;
use App\Services\Markets\CurrencyService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin CRUD for currencies (markets.manage).
 */
final class CurrencyController extends AdminController
{
    public function __construct(private readonly CurrencyService $service) {}

    public function index(CurrenciesDataTable $table): mixed
    {
        return $table->render('admin.currencies.index');
    }

    public function create(): View
    {
        return view('admin.currencies.create');
    }

    public function store(StoreCurrencyRequest $request): AdminFormResponse
    {
        $currency = $this->service->create(CurrencyData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.currencies.created'))->redirect(route('admin.currencies.show', $currency));
    }

    public function show(Currency $currency): View
    {
        return view('admin.currencies.show', ['currency' => $currency]);
    }

    public function activity(Currency $currency, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($currency)]);
    }

    public function edit(Currency $currency): View
    {
        return view('admin.currencies.edit', ['currency' => $currency]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): AdminFormResponse
    {
        $this->service->update($currency, CurrencyData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.currencies.updated'))->redirect(route('admin.currencies.show', $currency));
    }

    public function destroy(DestroyCurrencyRequest $request, Currency $currency): AdminFormResponse
    {
        $this->service->delete($currency, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.currencies.deleted'))->reloadTable(CurrenciesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.currencies.index')) : $response;
    }
}
