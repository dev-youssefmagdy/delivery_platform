<?php

namespace App\Http\Requests\Admin\Support;

use App\DTOs\Support\SupportMacroData;
use App\Enums\Support\TicketCategory;
use App\Models\SupportMacro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a macro (Arabic text required; English optional but complete or empty).
 */
class StoreMacroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', SupportMacro::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:120'],
            'title.en' => ['nullable', 'string', 'max:120', 'required_with:body.en'],
            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:2000'],
            'body.en' => ['nullable', 'string', 'max:2000', 'required_with:title.en'],
            'category' => ['nullable', 'string', Rule::enum(TicketCategory::class)],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.ar' => (string) __('admin.macros.fields.title').' (ar)',
            'title.en' => (string) __('admin.macros.fields.title').' (en)',
            'body.ar' => (string) __('admin.macros.fields.body').' (ar)',
            'body.en' => (string) __('admin.macros.fields.body').' (en)',
            'category' => (string) __('admin.macros.fields.category'),
        ];
    }

    public function toDto(): SupportMacroData
    {
        return SupportMacroData::fromRequest($this);
    }
}
