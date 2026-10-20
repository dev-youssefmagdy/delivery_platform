<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Common\Locale;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreVehicleCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', VehicleCategory::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtolower(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,39}$/', Rule::unique('vehicle_categories', 'code')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'map_marker' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'passenger_capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'luggage_capacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_payload_kg' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'max_volume_m3' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $name = __('admin.vehicle_categories.fields.name');
        $description = __('admin.vehicle_categories.fields.description');

        return [
            'code' => __('admin.catalog.fields.code'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'description.ar' => $description.' ('.Locale::Arabic->label().')',
            'description.en' => $description.' ('.Locale::English->label().')',
            'icon' => __('admin.catalog.fields.icon'),
            'map_marker' => __('admin.catalog.fields.map_marker'),
            'passenger_capacity' => __('admin.vehicle_categories.fields.passenger_capacity'),
            'luggage_capacity' => __('admin.vehicle_categories.fields.luggage_capacity'),
            'max_payload_kg' => __('admin.vehicle_categories.fields.max_payload_kg'),
            'max_volume_m3' => __('admin.vehicle_categories.fields.max_volume_m3'),
            'sort_order' => __('admin.catalog.fields.sort_order'),
        ];
    }
}
