<?php

namespace App\Http\Requests\Admin\Security;

use App\Enums\Common\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An admin edits their own profile (no permission needed: self-service).
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{6,32}$/'],
            'locale' => ['nullable', 'string', Rule::enum(Locale::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('admin.admins.fields.name'),
            'phone' => (string) __('admin.admins.fields.phone'),
            'locale' => (string) __('admin.admins.fields.locale'),
        ];
    }
}
