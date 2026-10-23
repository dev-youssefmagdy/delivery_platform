<?php

namespace App\Http\Requests\Admin\System;

use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use App\Models\FeatureFlag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create / update of a feature flag. On update the route carries the flag and its key is fixed.
 */
class SaveFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('featureFlag') instanceof FeatureFlag ? Gate::allows('update', FeatureFlag::class) : Gate::allows('create', FeatureFlag::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $editing = $this->route('featureFlag') instanceof FeatureFlag;

        return [
            'key' => $editing ? ['nullable'] : ['required', Rule::enum(FeatureFlagKey::class), Rule::unique('feature_flags', 'key')],
            'enabled' => ['nullable', 'boolean'],
            'apps' => ['nullable', 'array'],
            'apps.*' => ['string', Rule::enum(AppTarget::class)],
            'city_ids' => ['nullable', 'array', 'max:200'],
            'city_ids.*' => ['integer', 'exists:cities,id'],
            'rollout_percent' => ['required', 'integer', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:255'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key' => (string) __('system.flags.fields.key'),
            'apps' => (string) __('system.flags.fields.apps'),
            'city_ids' => (string) __('system.flags.fields.cities'),
            'rollout_percent' => (string) __('system.flags.fields.rollout'),
            'note' => (string) __('system.flags.fields.note'),
        ];
    }
}
