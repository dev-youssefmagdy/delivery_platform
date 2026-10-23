<?php

namespace App\Http\Controllers\Admin\System;

use App\DataTables\Admin\System\AppVersionsDataTable;
use App\DTOs\System\AppVersionData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\System\SaveAppVersionRequest;
use App\Models\AppVersion;
use App\Services\System\AppVersionService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * App versions (system.app_versions): minimum version, force update and store link per app and platform.
 */
final class AppVersionController extends AdminController
{
    public function __construct(private readonly AppVersionService $service) {}

    public function index(AppVersionsDataTable $table): mixed
    {
        return $table->render('admin.system.app-versions.index');
    }

    public function create(): View
    {
        return view('admin.system.app-versions.form', ['version' => null]);
    }

    public function store(SaveAppVersionRequest $request): AdminFormResponse
    {
        $this->service->create($this->actor(), AppVersionData::fromRequest($request));

        return $this->formResponse(__('system.app_versions.saved'))->redirect(route('admin.app-versions.index'));
    }

    public function edit(AppVersion $appVersion): View
    {
        return view('admin.system.app-versions.form', ['version' => $appVersion]);
    }

    public function update(SaveAppVersionRequest $request, AppVersion $appVersion): AdminFormResponse
    {
        $data = AppVersionData::fromRequest($request);
        // App and platform identify the row and are not editable.
        $data = new AppVersionData($appVersion->app, $appVersion->platform, $data->minVersion, $data->latestVersion, $data->forceUpdate, $data->storeUrl, $data->lockVersion);
        $this->service->update($appVersion, $this->actor(), $data);

        return $this->formResponse(__('system.app_versions.saved'))->redirect(route('admin.app-versions.index'));
    }
}
