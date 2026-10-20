<?php

namespace App\Http\Requests\Admin\Geography;

use App\Enums\Catalog\Weekday;
use App\Enums\Common\Locale;
use App\Models\ServiceZone;
use App\Support\Geo\GeoPolygon;
use App\Support\Geo\InvalidCoordinatesException;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ServiceZone::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'polygon' => ['required', 'string', 'max:100000'],
            'restricted_pickup' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'operating_hours' => ['nullable', 'array', 'max:28'],
            'operating_hours.*.day' => ['required', Rule::enum(Weekday::class)],
            'operating_hours.*.from' => ['required', 'date_format:H:i'],
            'operating_hours.*.to' => ['required', 'date_format:H:i', 'after:operating_hours.*.from'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('polygon') || ! is_string($this->input('polygon'))) {
                return;
            }

            try {
                GeoPolygon::fromLatLngList($this->input('polygon'));
            } catch (InvalidCoordinatesException) {
                $validator->errors()->add('polygon', __('admin.service_zones.errors.polygon'));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $name = __('admin.service_zones.fields.name');

        return [
            'city_id' => __('admin.service_zones.fields.city'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'polygon' => __('admin.service_zones.fields.polygon'),
            'restricted_pickup' => __('admin.service_zones.fields.restricted_pickup'),
            'notes' => __('admin.service_zones.fields.notes'),
            'operating_hours' => __('admin.service_zones.fields.operating_hours'),
            'operating_hours.*.day' => __('admin.service_zones.fields.hours_day'),
            'operating_hours.*.from' => __('admin.service_zones.fields.hours_from'),
            'operating_hours.*.to' => __('admin.service_zones.fields.hours_to'),
        ];
    }
}
