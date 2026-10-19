<?php

namespace App\Http\Controllers\Admin\Settings;

use App\DTOs\Settings\SettingsData;
use App\Enums\Settings\SettingGroup;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Typed platform settings (settings.view / settings.manage): one tab per SettingGroup, fields generated from
 * SettingKey metadata.
 */
final class SettingsController extends AdminController
{
    public function __construct(private readonly SettingsService $service) {}

    public function index(): View
    {
        $tabs = [];

        foreach (SettingGroup::cases() as $group) {
            $tabs[$group->value] = [
                'group' => $group,
                'fields' => $this->service->fieldsFor($group),
                'fingerprint' => $this->service->fingerprint($group),
            ];
        }

        $active = SettingGroup::tryFromValue(request()->query('tab'));

        return view('admin.settings.index', ['tabs' => $tabs, 'active' => ($active ?? SettingGroup::cases()[0])->value]);
    }

    public function update(UpdateSettingsRequest $request, SettingGroup $group): AdminFormResponse
    {
        $changed = $this->service->updateGroup(SettingsData::fromRequest($request), $this->actor());

        return $this->formResponse($changed === [] ? __('admin.settings.unchanged') : __('admin.settings.saved', ['group' => $group->label()]))
            ->redirect(route('admin.settings.index', ['tab' => $group->value]));
    }
}
