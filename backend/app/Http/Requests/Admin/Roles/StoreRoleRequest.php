<?php

namespace App\Http\Requests\Admin\Roles;

use App\Enums\Admin\AdminPermission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Role::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'name')->where('guard_name', 'admin')],
            'label' => ['required', 'array'],
            'label.ar' => ['required', 'string', 'max:191'],
            'label.en' => ['nullable', 'string', 'max:191'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AdminPermission::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('admin.roles.fields.name'),
            'label' => (string) __('admin.roles.fields.label'),
            'label.ar' => (string) __('admin.roles.fields.label').' (ar)',
            'label.en' => (string) __('admin.roles.fields.label').' (en)',
            'permissions' => (string) __('admin.roles.fields.permissions'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.regex' => (string) __('admin.roles.hints.name')];
    }
}
