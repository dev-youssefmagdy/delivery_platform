<?php

namespace App\Http\Requests\Api\V1\Me;

use App\DTOs\Identity\AcceptConsentData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /me/consents. `policies` are the ids served by /config/app and /policies/{type}.
 */
class AcceptConsentRequest extends FormRequest
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
            'policies' => ['required', 'array', 'min:1', 'max:10'],
            'policies.*' => ['required', 'string', 'uuid', 'distinct'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): AcceptConsentData
    {
        /** @var list<string> $policies */
        $policies = array_values($this->validated('policies'));

        return new AcceptConsentData(
            policyUuids: $policies,
            locale: app()->getLocale(),
            ip: $this->ip(),
            marketingOptIn: $this->has('marketing_opt_in') ? $this->boolean('marketing_opt_in') : null,
        );
    }
}
