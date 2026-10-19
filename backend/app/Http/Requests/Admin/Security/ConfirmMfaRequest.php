<?php

namespace App\Http\Requests\Admin\Security;

use Illuminate\Foundation\Http\FormRequest;

/**
 * First authenticator code that confirms MFA enrolment.
 */
class ConfirmMfaRequest extends FormRequest
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
        return ['code' => ['required', 'string', 'regex:/^\d{6}$/']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.regex' => (string) __('admin.auth.mfa.code_format_totp')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => (string) __('admin.auth.fields.code')];
    }
}
