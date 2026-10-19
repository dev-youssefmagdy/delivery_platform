<?php

namespace App\Http\Requests\Admin\Auth;

use App\DTOs\Admin\LoginData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin login form. Only the shape is validated here (and by the Precognition call); the credentials check is
 * the service's job and always answers with one generic message.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => (string) __('admin.auth.fields.email'),
            'password' => (string) __('admin.auth.fields.password'),
        ];
    }

    public function toDto(): LoginData
    {
        return new LoginData(
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
            remember: $this->boolean('remember'),
        );
    }
}
