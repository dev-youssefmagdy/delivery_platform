<?php

namespace App\Http\Requests\Api\V1\Me;

use App\DTOs\Identity\UpdateProfileData;
use App\Enums\Common\Locale;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PATCH /me. Only these fields are read: `phone`, `status`, `id` and anything else in the body are ignored.
 */
class UpdateProfileRequest extends FormRequest
{
    private const USER_FIELDS = ['name', 'email', 'preferred_locale'];

    private const CUSTOMER_FIELDS = ['accessibility_needs', 'emergency_contact_name', 'emergency_contact_phone', 'marketing_opt_in'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('emergency_contact_phone');

        if (is_string($phone) && $phone !== '') {
            $this->merge(['emergency_contact_phone' => PhoneNumber::normalize($phone) ?? $phone]);
        }

        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:190'],
            'preferred_locale' => ['sometimes', 'string', Rule::enum(Locale::class)],
            'accessibility_needs' => ['sometimes', 'nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'regex:'.PhoneNumber::PATTERN],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): UpdateProfileData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new UpdateProfileData(
            user: array_intersect_key($validated, array_flip(self::USER_FIELDS)),
            customer: array_intersect_key($validated, array_flip(self::CUSTOMER_FIELDS)),
        );
    }
}
