<?php

namespace App\Http\Requests\Admin\Admins;

use App\Enums\Common\Locale;
use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Admin::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('admins', 'email')->ignore($this->route('admin'))],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{6,32}$/'],
            'locale' => ['nullable', 'string', Rule::enum(Locale::class)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('admin.admins.fields.name'),
            'email' => (string) __('admin.admins.fields.email'),
            'phone' => (string) __('admin.admins.fields.phone'),
            'locale' => (string) __('admin.admins.fields.locale'),
            'roles' => (string) __('admin.admins.fields.roles'),
        ];
    }
}
