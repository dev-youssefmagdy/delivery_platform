<?php

namespace App\Http\Requests\Admin\Policy;

use App\Enums\Common\Locale;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePolicyVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PolicyVersion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PolicyType::class)],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:191'],
            'title.en' => ['nullable', 'string', 'max:191'],
            'content' => ['required', 'array'],
            'content.ar' => ['required', 'string', 'max:100000'],
            'content.en' => ['nullable', 'string', 'max:100000'],
            'change_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => __('admin.policy_versions.fields.type'),
            'title' => __('admin.policy_versions.fields.title'),
            'title.ar' => __('admin.policy_versions.fields.title').' ('.Locale::Arabic->label().')',
            'title.en' => __('admin.policy_versions.fields.title').' ('.Locale::English->label().')',
            'content' => __('admin.policy_versions.fields.content'),
            'content.ar' => __('admin.policy_versions.fields.content').' ('.Locale::Arabic->label().')',
            'content.en' => __('admin.policy_versions.fields.content').' ('.Locale::English->label().')',
            'change_note' => __('admin.policy_versions.fields.change_note'),
        ];
    }
}
