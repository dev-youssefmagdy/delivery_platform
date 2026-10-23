<?php

namespace App\Http\Requests\Admin\System;

use App\Enums\Settings\SettingKey;
use App\Enums\System\AppPlatform;
use App\Enums\System\AppTarget;
use App\Models\AppVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create / update of an app version row. Used for both: on update the route carries the row (app and platform are then fixed).
 */
class SaveAppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('appVersion') instanceof AppVersion ? Gate::allows('update', AppVersion::class) : Gate::allows('create', AppVersion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $editing = $this->route('appVersion') instanceof AppVersion;

        return [
            'app' => [$editing ? 'nullable' : 'required', Rule::enum(AppTarget::class)],
            'platform' => [$editing ? 'nullable' : 'required', Rule::enum(AppPlatform::class)],
            'min_version' => ['required', 'string', 'regex:'.SettingKey::VERSION_PATTERN],
            'latest_version' => ['nullable', 'string', 'regex:'.SettingKey::VERSION_PATTERN],
            'force_update' => ['nullable', 'boolean'],
            // Store pages only: https, so the update screen never opens an arbitrary scheme.
            'store_url' => ['nullable', 'string', 'max:500', 'url:https'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'app' => (string) __('system.app_versions.fields.app'),
            'platform' => (string) __('system.app_versions.fields.platform'),
            'min_version' => (string) __('system.app_versions.fields.min_version'),
            'latest_version' => (string) __('system.app_versions.fields.latest_version'),
            'store_url' => (string) __('system.app_versions.fields.store_url'),
        ];
    }
}
