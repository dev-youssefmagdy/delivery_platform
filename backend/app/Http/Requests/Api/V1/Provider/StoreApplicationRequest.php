<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Provider\ProviderApplicationData;
use App\Enums\Provider\ProviderType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /provider/applications: starts the application (type, city, name, birth date, optional services).
 */
class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
            'provider_type' => ['required', Rule::enum(ProviderType::class)],
            'city_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01', Rule::requiredIf(fn (): bool => $this->input('provider_type') !== ProviderType::CompanyFleet->value)],
            'service_category_ids' => ['sometimes', 'array', 'max:30'],
            'service_category_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function toDto(): ProviderApplicationData
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new ProviderApplicationData(
            providerType: isset($data['provider_type']) ? ProviderType::from((string) $data['provider_type']) : null,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            dateOfBirth: isset($data['date_of_birth']) ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $data['date_of_birth']) ?: null : null,
            serviceCategoryIds: array_key_exists('service_category_ids', $data) ? array_values(array_map('intval', (array) $data['service_category_ids'])) : null,
        );
    }
}
