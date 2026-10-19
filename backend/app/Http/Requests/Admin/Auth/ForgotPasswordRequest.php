<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
        return ['email' => ['required', 'string', 'email:rfc', 'max:191']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => (string) __('admin.auth.fields.email')];
    }
}
