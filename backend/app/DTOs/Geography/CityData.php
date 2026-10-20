<?php

namespace App\DTOs\Geography;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Http\Requests\Admin\Geography\StoreCityRequest;
use App\Http\Requests\Admin\Geography\UpdateCityRequest;
use App\Support\Geo\GeoPoint;

/**
 * Validated input for creating/updating a city. New cities start inactive; activation is a separate audited action.
 */
final readonly class CityData
{
    /**
     * @param array<string, string|null> $name
     */
    public function __construct(
        public int $countryId,
        public array $name,
        public string $timezone,
        public GeoPoint $center,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCityRequest|UpdateCityRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            countryId: (int) $data['country_id'],
            name: FormFieldDefinition::translations($data['name'] ?? []),
            timezone: (string) $data['timezone'],
            center: GeoPoint::make($data['center_lat'], $data['center_lng']),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'country_id' => $this->countryId,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'center' => $this->center,
        ];
    }
}
