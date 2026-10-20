<?php

namespace App\Http\Requests\Admin\Provider;

use App\DTOs\Provider\DocumentRequirementData;
use App\Enums\Provider\ProviderType;
use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Validation\Rule;

/**
 * Add a requirement to a document type. Every scope is optional (empty = everyone); a reason is mandatory because
 * requirements decide who is eligible.
 */
class StoreDocumentRequirementRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'update';
    }

    protected function parameter(): string
    {
        return 'documentType';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mandatory' => $this->boolean('mandatory')]);

        foreach (['country_id', 'city_id', 'service_category_id', 'vehicle_category_id', 'provider_type'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true) + [
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')],
            'vehicle_category_id' => ['nullable', 'integer', Rule::exists('vehicle_categories', 'id')],
            'provider_type' => ['nullable', Rule::enum(ProviderType::class)],
            'mandatory' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => (string) __('admin.common.reason'),
            'country_id' => (string) __('admin.document_types.fields.country'),
            'city_id' => (string) __('admin.document_types.fields.city'),
            'service_category_id' => (string) __('admin.document_types.fields.service_category'),
            'vehicle_category_id' => (string) __('admin.document_types.fields.vehicle_category'),
            'provider_type' => (string) __('admin.document_types.fields.provider_type'),
        ];
    }

    public function toDto(): DocumentRequirementData
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $id = static fn (string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;

        return new DocumentRequirementData(
            countryId: $id('country_id'),
            cityId: $id('city_id'),
            serviceCategoryId: $id('service_category_id'),
            vehicleCategoryId: $id('vehicle_category_id'),
            providerType: isset($data['provider_type']) ? ProviderType::from((string) $data['provider_type']) : null,
            mandatory: (bool) ($data['mandatory'] ?? true),
        );
    }
}
