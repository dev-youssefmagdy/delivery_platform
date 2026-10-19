<?php

namespace App\Http\Requests\Admin\Markets;

use App\Enums\Common\Locale;
use App\Models\Country;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Country::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('iso2'))) {
            $this->merge(['iso2' => strtoupper(trim($this->input('iso2')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'iso2' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/', Rule::unique('countries', 'iso2')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'phone_code' => ['required', 'string', 'regex:/^\+\d{1,4}$/'],
            'currency_code' => ['required', 'string', Rule::exists('currencies', 'code')->where('active', true)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'default_locale' => ['required', Rule::enum(Locale::class)],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'iso2' => __('admin.countries.fields.iso2'),
            'name' => __('admin.countries.fields.name'),
            'name.ar' => __('admin.countries.fields.name').' ('.Locale::Arabic->label().')',
            'name.en' => __('admin.countries.fields.name').' ('.Locale::English->label().')',
            'phone_code' => __('admin.countries.fields.phone_code'),
            'currency_code' => __('admin.countries.fields.currency'),
            'timezone' => __('admin.countries.fields.timezone'),
            'default_locale' => __('admin.countries.fields.default_locale'),
            'active' => __('admin.countries.fields.active'),
        ];
    }
}
