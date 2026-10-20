<?php

namespace App\Http\Requests\Admin\Provider;

use App\Enums\Common\Locale;
use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', DocumentType::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtolower(trim($this->input('code')))]);
        }

        $this->merge([
            'requires_expiry' => $this->boolean('requires_expiry'),
            'requires_number' => $this->boolean('requires_number'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,39}$/', Rule::unique('document_types', 'code')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'applies_to' => ['required', Rule::enum(DocumentSubject::class)],
            'requires_expiry' => ['boolean'],
            'requires_number' => ['boolean'],
            'allowed_mimes' => ['required', 'array', 'min:1'],
            'allowed_mimes.*' => ['string', Rule::in(array_keys((array) config('provider.documents.mime_extensions', [])))],
            'max_size_kb' => ['required', 'integer', 'min:100', 'max:'.(int) config('provider.documents.max_kilobytes', 10240)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $name = __('admin.document_types.fields.name');
        $description = __('admin.document_types.fields.description');

        return [
            'code' => __('admin.catalog.fields.code'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'description.ar' => $description.' ('.Locale::Arabic->label().')',
            'description.en' => $description.' ('.Locale::English->label().')',
            'applies_to' => __('admin.document_types.fields.applies_to'),
            'requires_expiry' => __('admin.document_types.fields.requires_expiry'),
            'requires_number' => __('admin.document_types.fields.requires_number'),
            'allowed_mimes' => __('admin.document_types.fields.allowed_mimes'),
            'max_size_kb' => __('admin.document_types.fields.max_size_kb'),
            'sort_order' => __('admin.catalog.fields.sort_order'),
        ];
    }
}
