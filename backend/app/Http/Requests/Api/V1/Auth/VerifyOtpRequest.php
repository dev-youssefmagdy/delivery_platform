<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Identity\DeviceData;
use App\DTOs\Identity\VerifyOtpData;
use App\Enums\Common\Platform;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpPurpose;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /auth/otp/verify.
 */
class VerifyOtpRequest extends FormRequest
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
            'code' => ['required', 'string', 'regex:/^[0-9٠-٩\s]{4,12}$/u'],
            'purpose' => ['sometimes', 'string', Rule::enum(OtpPurpose::class)],
            'device' => ['required', 'array'],
            'device.device_id' => ['required', 'string', 'max:100'],
            'device.platform' => ['required', 'string', Rule::enum(Platform::class)],
            'device.app_version' => ['nullable', 'string', 'max:32'],
            'device.push_token' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function toDto(): VerifyOtpData
    {
        /** @var array<string, mixed> $device */
        $device = $this->validated('device');

        return new VerifyOtpData(
            phone: (string) $this->validated('phone'),
            code: (string) $this->validated('code'),
            purpose: OtpPurpose::tryFrom((string) $this->validated('purpose', OtpPurpose::Login->value)) ?? OtpPurpose::Login,
            app: ClientApp::from((string) $this->validated('app')),
            device: new DeviceData(
                deviceId: (string) $device['device_id'],
                platform: Platform::from((string) $device['platform']),
                appVersion: isset($device['app_version']) ? (string) $device['app_version'] : null,
                pushToken: isset($device['push_token']) ? (string) $device['push_token'] : null,
                locale: app()->getLocale(),
            ),
        );
    }
}
