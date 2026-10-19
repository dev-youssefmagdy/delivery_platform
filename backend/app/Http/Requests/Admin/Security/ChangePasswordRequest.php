<?php

namespace App\Http\Requests\Admin\Security;

use App\Support\Auth\AdminPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', AdminPassword::rule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => (string) __('admin.auth.fields.current_password'),
            'password' => (string) __('admin.auth.fields.new_password'),
        ];
    }
}
