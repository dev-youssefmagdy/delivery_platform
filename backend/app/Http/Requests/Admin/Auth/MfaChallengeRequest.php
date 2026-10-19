<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Second login step: a 6-digit authenticator code or a recovery code (xxxxx-xxxxx).
 */
class MfaChallengeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:32', 'regex:/^(\d{6}|[a-zA-Z0-9]{5}-[a-zA-Z0-9]{5})$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.regex' => (string) __('admin.auth.mfa.code_format')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => (string) __('admin.auth.fields.code')];
    }

    public function code(): string
    {
        return trim((string) $this->validated('code'));
    }
}
