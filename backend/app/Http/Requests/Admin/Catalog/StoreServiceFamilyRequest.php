<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\ServiceFamilyKind;
use App\Enums\Common\Locale;
use App\Models\ServiceFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreServiceFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ServiceFamily::class);
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
            'code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,39}$/', Rule::unique('service_families', 'code')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'kind' => ['required', Rule::enum(ServiceFamilyKind::class)],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $name = __('admin.service_families.fields.name');
        $description = __('admin.service_families.fields.description');

        return [
            'code' => __('admin.catalog.fields.code'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'description.ar' => $description.' ('.Locale::Arabic->label().')',
            'description.en' => $description.' ('.Locale::English->label().')',
            'kind' => __('admin.service_families.fields.kind'),
            'icon' => __('admin.catalog.fields.icon'),
            'sort_order' => __('admin.catalog.fields.sort_order'),
        ];
    }
}
