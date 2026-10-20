<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\Enums\Provider\ProviderType;
use Illuminate\Validation\Rule;

/**
 * PATCH /provider/application: any subset of the application fields.
 */
class UpdateApplicationRequest extends StoreApplicationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_type' => ['sometimes', Rule::enum(ProviderType::class)],
            'city_id' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'date_of_birth' => ['sometimes', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'service_category_ids' => ['sometimes', 'array', 'max:30'],
            'service_category_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }
}
