<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Identity\RequestOtpData;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpPurpose;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /auth/otp/request. The app comes from the `X-App` header, the phone is normalised to E.164.
 */
class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        $this->merge([
            'app' => $this->header(ClientApp::HEADER),
            'phone' => is_string($phone) ? (PhoneNumber::normalize($phone) ?? $phone) : $phone,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app' => ['required', 'string', Rule::enum(ClientApp::class)],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'purpose' => ['sometimes', 'string', Rule::enum(OtpPurpose::class)],
            'device_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDto(): RequestOtpData
    {
        return new RequestOtpData(
            phone: (string) $this->validated('phone'),
            purpose: OtpPurpose::tryFrom((string) $this->validated('purpose', OtpPurpose::Login->value)) ?? OtpPurpose::Login,
            app: ClientApp::from((string) $this->validated('app')),
            ip: $this->ip(),
            deviceId: $this->validated('device_id'),
        );
    }
}
