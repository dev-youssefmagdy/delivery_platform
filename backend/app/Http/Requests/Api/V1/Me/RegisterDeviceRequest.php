<?php

namespace App\Http\Requests\Api\V1\Me;

use App\DTOs\Identity\DeviceData;
use App\Enums\Common\Locale;
use App\Enums\Common\Platform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /me/devices.
 */
class RegisterDeviceRequest extends FormRequest
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
            'device_id' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'string', Rule::enum(Platform::class)],
            'app_version' => ['nullable', 'string', 'max:32'],
            'push_token' => ['nullable', 'string', 'max:4096'],
            'locale' => ['nullable', 'string', Rule::enum(Locale::class)],
        ];
    }

    public function toDto(): DeviceData
    {
        return new DeviceData(
            deviceId: (string) $this->validated('device_id'),
            platform: Platform::from((string) $this->validated('platform')),
            appVersion: $this->validated('app_version'),
            pushToken: $this->validated('push_token'),
            locale: $this->validated('locale') ?? app()->getLocale(),
        );
    }
}
