<?php

namespace App\Http\Requests\Admin\Geography;

use App\Models\ServiceZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The availability matrix of one zone: `categories[<id>][active|min_fare_minor|max_fare_minor|dispatch_radius_steps_km|hours_from|hours_to]`.
 */
class UpdateZoneAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $zone = $this->route('serviceZone');

        return $zone instanceof ServiceZone && Gate::allows('update', $zone);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'categories' => ['nullable', 'array', 'max:200'],
            'categories.*' => ['array'],
            'categories.*.active' => ['nullable', 'boolean'],
            'categories.*.min_fare_minor' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'categories.*.max_fare_minor' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'categories.*.dispatch_radius_steps_km' => ['nullable', 'string', 'max:100', 'regex:/^\s*\d+(\.\d+)?(\s*,\s*\d+(\.\d+)?)*\s*$/'],
            'categories.*.hours_from' => ['nullable', 'date_format:H:i', 'required_with:categories.*.hours_to'],
            'categories.*.hours_to' => ['nullable', 'date_format:H:i', 'required_with:categories.*.hours_from', 'after:categories.*.hours_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'categories.*.min_fare_minor' => __('admin.service_zones.availability.min_fare'),
            'categories.*.max_fare_minor' => __('admin.service_zones.availability.max_fare'),
            'categories.*.dispatch_radius_steps_km' => __('admin.service_zones.availability.radius'),
            'categories.*.hours_from' => __('admin.service_zones.availability.hours_from'),
            'categories.*.hours_to' => __('admin.service_zones.availability.hours_to'),
        ];
    }
}
