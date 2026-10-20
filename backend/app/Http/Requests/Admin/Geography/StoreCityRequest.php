<?php

namespace App\Http\Requests\Admin\Geography;

use App\Enums\Common\Locale;
use App\Models\City;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', City::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'timezone' => ['required', 'string', 'max:64', 'timezone:all'],
            'center_lat' => ['required', 'numeric', 'between:-90,90'],
            'center_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function ($validator): void {
            if (! $validator->errors()->hasAny(['center_lat', 'center_lng']) && (float) $this->input('center_lat') === 0.0 && (float) $this->input('center_lng') === 0.0) {
                $validator->errors()->add('center_lat', __('validation.coordinates'));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $name = __('admin.cities.fields.name');

        return [
            'country_id' => __('admin.cities.fields.country'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'timezone' => __('admin.cities.fields.timezone'),
            'center_lat' => __('admin.form.lat'),
            'center_lng' => __('admin.form.lng'),
        ];
    }
}
