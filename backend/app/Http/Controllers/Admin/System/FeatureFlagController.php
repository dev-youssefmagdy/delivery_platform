<?php

namespace App\Http\Controllers\Admin\System;

use App\DataTables\Admin\System\FeatureFlagsDataTable;
use App\DTOs\System\FeatureFlagData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\System\SaveFeatureFlagRequest;
use App\Models\City;
use App\Models\FeatureFlag;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Services\System\FeatureFlagService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Feature flags (system.feature_flags): switch, apps, cities and rollout percentage.
 */
final class FeatureFlagController extends AdminController
{
    public function __construct(private readonly FeatureFlagService $service, private readonly CityRepositoryInterface $cities) {}

    public function index(FeatureFlagsDataTable $table): mixed
    {
        return $table->render('admin.system.feature-flags.index');
    }

    public function create(): View
    {
        return view('admin.system.feature-flags.form', ['flag' => null, 'selectedCities' => []]);
    }

    public function store(SaveFeatureFlagRequest $request): AdminFormResponse
    {
        $this->service->save($this->actor(), FeatureFlagData::fromRequest($request));

        return $this->formResponse(__('system.flags.saved'))->redirect(route('admin.feature-flags.index'));
    }

    public function edit(FeatureFlag $featureFlag): View
    {
        $selected = $this->cities->findManyByIds(array_map('intval', $featureFlag->city_ids ?? []))
            ->mapWithKeys(static fn (City $city): array => [$city->id => (string) $city->name])->all();

        return view('admin.system.feature-flags.form', ['flag' => $featureFlag, 'selectedCities' => $selected]);
    }

    public function update(SaveFeatureFlagRequest $request, FeatureFlag $featureFlag): AdminFormResponse
    {
        $data = FeatureFlagData::fromRequest($request);
        // The key identifies the row and is not editable.
        $this->service->save($this->actor(), new FeatureFlagData($featureFlag->key, $data->enabled, $data->apps, $data->cityIds, $data->rolloutPercent, $data->note, $data->lockVersion));

        return $this->formResponse(__('system.flags.saved'))->redirect(route('admin.feature-flags.index'));
    }
}
