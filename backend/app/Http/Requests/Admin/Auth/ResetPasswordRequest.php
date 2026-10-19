<?php

namespace App\Http\Requests\Admin\Auth;

use App\DTOs\Admin\PasswordResetData;
use App\Support\Auth\AdminPassword;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'password' => ['required', 'string', 'confirmed', AdminPassword::rule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => (string) __('admin.auth.fields.email'),
            'password' => (string) __('admin.auth.fields.new_password'),
        ];
    }

    public function toDto(): PasswordResetData
    {
        return new PasswordResetData(
            email: (string) $this->validated('email'),
            token: (string) $this->validated('token'),
            password: (string) $this->validated('password'),
        );
    }
}
