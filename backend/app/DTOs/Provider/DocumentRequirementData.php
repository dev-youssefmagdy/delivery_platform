<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\ProviderType;

/**
 * Validated input of adding a requirement to a document type: all scopes are optional (null = everyone).
 */
final readonly class DocumentRequirementData
{
    public function __construct(
        public ?int $countryId = null,
        public ?int $cityId = null,
        public ?int $serviceCategoryId = null,
        public ?int $vehicleCategoryId = null,
        public ?ProviderType $providerType = null,
        public bool $mandatory = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
            'service_category_id' => $this->serviceCategoryId,
            'vehicle_category_id' => $this->vehicleCategoryId,
            'provider_type' => $this->providerType,
            'mandatory' => $this->mandatory,
        ];
    }
}
