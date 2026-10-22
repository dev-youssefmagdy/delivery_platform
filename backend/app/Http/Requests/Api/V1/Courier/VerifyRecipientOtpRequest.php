<?php

namespace App\Http\Requests\Api\V1\Courier;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/bookings/{booking}/verify-recipient-otp. The code is digits only (Arabic-Indic digits are accepted and
 * normalised by the service); the length is the configured one.
 */
class VerifyRecipientOtpRequest extends FormRequest
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
        return ['code' => ['required', 'string', 'regex:/^[\s0-9٠-٩]{4,12}$/u']];
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }
}
