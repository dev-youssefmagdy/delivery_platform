<?php

namespace App\DTOs\Geography;

use App\Enums\Geo\LocationPrecision;
use App\Enums\Geo\SavedPlaceType;
use App\Support\Geo\GeoPoint;

/**
 * Validated input for creating/updating a saved place.
 */
final readonly class SavedPlaceData
{
    /**
     * @param array{title: string|null, line: string|null, precision: string|null, provider: string|null}|null $address client-supplied geocoder snapshot (a hint only)
     */
    public function __construct(
        public string $label,
        public SavedPlaceType $type,
        public GeoPoint $point,
        public ?array $address,
    ) {}

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        $address = is_array($validated['address'] ?? null) ? $validated['address'] : null;
        $text = static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;

        return new self(
            label: trim((string) $validated['label']),
            type: SavedPlaceType::from((string) $validated['type']),
            point: GeoPoint::make($validated['lat'], $validated['lng']),
            address: $address === null ? null : [
                'title' => $text($address['title'] ?? null),
                'line' => $text($address['line'] ?? null),
                'precision' => isset($address['precision']) ? LocationPrecision::tryFrom((string) $address['precision'])?->value : null,
                'provider' => $text($address['provider'] ?? null),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return ['label' => $this->label, 'type' => $this->type, 'point' => $this->point, 'address' => $this->address];
    }
}
