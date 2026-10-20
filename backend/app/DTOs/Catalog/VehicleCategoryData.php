<?php

namespace App\DTOs\Catalog;

use App\Http\Requests\Admin\Catalog\StoreVehicleCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateVehicleCategoryRequest;
use App\Models\VehicleCategory;

/**
 * Validated input for creating/updating a vehicle category (capacity fields, icon, map marker).
 */
final readonly class VehicleCategoryData
{
    /**
     * @param array<string, string|null> $name
     * @param array<string, string|null> $description
     */
    public function __construct(
        public string $code,
        public array $name,
        public array $description,
        public ?string $icon,
        public ?string $mapMarker,
        public ?int $passengerCapacity,
        public ?int $luggageCapacity,
        public ?string $maxPayloadKg,
        public ?string $maxVolumeM3,
        public int $sortOrder,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreVehicleCategoryRequest|UpdateVehicleCategoryRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('vehicleCategory');
        $text = static fn (string $key): ?string => ($value = trim((string) ($data[$key] ?? ''))) === '' ? null : $value;
        $int = static fn (string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;

        return new self(
            code: strtolower(trim((string) ($data['code'] ?? ($current instanceof VehicleCategory ? $current->code : '')))),
            name: FormFieldDefinition::translations($data['name'] ?? []),
            description: FormFieldDefinition::translations($data['description'] ?? []),
            icon: $text('icon'),
            mapMarker: $text('map_marker'),
            passengerCapacity: $int('passenger_capacity'),
            luggageCapacity: $int('luggage_capacity'),
            maxPayloadKg: $text('max_payload_kg'),
            maxVolumeM3: $text('max_volume_m3'),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description === [] ? null : $this->description,
            'icon' => $this->icon,
            'map_marker' => $this->mapMarker,
            'passenger_capacity' => $this->passengerCapacity,
            'luggage_capacity' => $this->luggageCapacity,
            'max_payload_kg' => $this->maxPayloadKg,
            'max_volume_m3' => $this->maxVolumeM3,
            'sort_order' => $this->sortOrder,
        ];
    }
}
