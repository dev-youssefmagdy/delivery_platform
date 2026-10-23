<?php

namespace App\Http\Requests\Api\V1\Safety;

use App\DTOs\Safety\TrustedContactData;
use App\Enums\Safety\TrustedContactRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST / PATCH /safety/trusted-contacts. `consent_confirmed` must be true: the person confirms the contact agreed to be messaged.
 */
class TrustedContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:'.(int) config('safety.trusted_contacts.name_max_chars', 80)],
            'phone' => ['required', 'string', 'max:24'],
            'relationship' => ['nullable', 'string', Rule::enum(TrustedContactRelationship::class)],
            'consent_confirmed' => ['required', 'accepted'],
            'notify_on_sos' => ['nullable', 'boolean'],
            'share_trip' => ['nullable', 'boolean'],
            'share_location_on_sos' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): TrustedContactData
    {
        return new TrustedContactData(
            (string) $this->input('name'),
            (string) $this->input('phone'),
            $this->filled('relationship') ? TrustedContactRelationship::from((string) $this->input('relationship')) : null,
            $this->boolean('consent_confirmed'),
            $this->boolean('notify_on_sos', true),
            $this->boolean('share_trip', true),
            $this->boolean('share_location_on_sos'),
        );
    }
}
