<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\Settings\SettingGroup;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * One settings tab. Rules, normalization and labels all come from SettingKey, so the same metadata that renders
 * the form validates it (the Precognition call and the real submit run these exact rules).
 */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', PlatformSetting::class);
    }

    public function group(): SettingGroup
    {
        $group = $this->route('group');

        return $group instanceof SettingGroup ? $group : SettingGroup::from((string) $group);
    }

    protected function prepareForValidation(): void
    {
        $input = $this->input('settings');
        $input = is_array($input) ? $input : [];
        $normalized = [];

        foreach ($this->group()->keys() as $key) {
            $normalized[$key->value] = $key->fromInput($input[$key->value] ?? null);
        }

        $this->merge(['settings' => $normalized]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['fingerprint' => ['nullable', 'string', 'size:40'], 'settings' => ['required', 'array']];

        foreach ($this->group()->keys() as $key) {
            $rules["settings.{$key->value}"] = $key->rules();

            if ($key->itemRules() !== []) {
                $rules["settings.{$key->value}.*"] = $key->itemRules();
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->group()->keys() as $key) {
            $attributes["settings.{$key->value}"] = $key->label();
            $attributes["settings.{$key->value}.*"] = $key->label();
        }

        return $attributes;
    }
}
