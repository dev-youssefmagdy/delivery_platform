<?php

namespace App\Http\Requests\Api\V1\Trip;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/bookings/{booking}/verify-start-pin: the digits the customer reads out.
 */
class VerifyStartPinRequest extends FormRequest
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
        return ['pin' => ['required', 'string', 'regex:/^\d{4,8}$/']];
    }

    public function pin(): string
    {
        return (string) $this->validated('pin');
    }
}
