<?php

namespace App\DTOs\Geography;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Http\Requests\Admin\Geography\StoreServiceZoneRequest;
use App\Http\Requests\Admin\Geography\UpdateServiceZoneRequest;
use App\Support\Geo\GeoPolygon;

/**
 * Validated input for creating/updating a service zone. New zones start as drafts; going live is a separate
 * audited action.
 */
final readonly class ServiceZoneData
{
    /**
     * @param array<string, string|null> $name
     * @param list<array{day: int, from: string, to: string}> $operatingHours empty = always open
     */
    public function __construct(
        public int $cityId,
        public array $name,
        public GeoPolygon $polygon,
        public array $operatingHours,
        public bool $restrictedPickup,
        public ?string $notes,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreServiceZoneRequest|UpdateServiceZoneRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $notes = trim((string) ($data['notes'] ?? ''));

        return new self(
            cityId: (int) $data['city_id'],
            name: FormFieldDefinition::translations($data['name'] ?? []),
            polygon: GeoPolygon::fromLatLngList($data['polygon']),
            operatingHours: self::hours((array) ($data['operating_hours'] ?? [])),
            restrictedPickup: filter_var($data['restricted_pickup'] ?? false, FILTER_VALIDATE_BOOLEAN),
            notes: $notes === '' ? null : $notes,
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @param array<array-key, mixed> $rows
     * @return list<array{day: int, from: string, to: string}>
     */
    public static function hours(array $rows): array
    {
        $hours = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $hours[] = ['day' => (int) $row['day'], 'from' => (string) $row['from'], 'to' => (string) $row['to']];
            }
        }

        usort($hours, static fn (array $a, array $b): int => [$a['day'], $a['from']] <=> [$b['day'], $b['from']]);

        return $hours;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'city_id' => $this->cityId,
            'name' => $this->name,
            'polygon' => $this->polygon,
            'operating_hours' => $this->operatingHours === [] ? null : $this->operatingHours,
            'restricted_pickup' => $this->restrictedPickup,
            'notes' => $this->notes,
        ];
    }
}
