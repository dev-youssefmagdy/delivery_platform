<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * A bookable category as of its published version (ADR-65): the snapshot is what clients and bookings use, so
 * later admin edits never change what a client already fetched until they publish. `version.id` is the id
 * bookings will reference; `form_schema.fields` is the exact schema ServiceFormValidator validates against.
 *
 * @property ServiceCategory $resource
 */
class ServiceCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->resource->publishedVersion;
        $snapshot = $version->snapshot;
        $category = $snapshot['category'];
        $vehicles = array_map(static fn (array $vehicle): array => [
            'id' => $vehicle['id'],
            'code' => $vehicle['code'],
            'name' => TranslationMap::of((array) $vehicle['name']),
            'icon' => $vehicle['icon'],
            'map_marker' => $vehicle['map_marker'],
            'passenger_capacity' => $vehicle['passenger_capacity'],
            'luggage_capacity' => $vehicle['luggage_capacity'],
            'max_payload_kg' => $vehicle['max_payload_kg'],
            'max_volume_m3' => $vehicle['max_volume_m3'],
        ], (array) $snapshot['vehicle_categories']);

        return [
            'id' => $this->resource->id,
            'code' => $category['code'],
            'version' => ['id' => $version->id, 'number' => $version->version, 'published_at' => $version->created_at->toIso8601ZuluString()],
            'family' => [
                'id' => $category['family']['id'],
                'code' => $category['family']['code'],
                'kind' => $category['family']['kind'],
                'name' => TranslationMap::of((array) $category['family']['name']),
            ],
            'name' => TranslationMap::of((array) $category['name']),
            'description' => TranslationMap::of((array) $category['description']),
            'icon' => $category['icon'],
            'map_marker' => $category['map_marker'],
            'sort_order' => $category['sort_order'],
            'booking' => $snapshot['booking'],
            'pricing' => $snapshot['pricing'],
            'dispatch' => $snapshot['dispatch'],
            'limits' => $snapshot['limits'],
            'safety' => $snapshot['safety'],
            'payments' => $snapshot['payments'],
            'zone_availability' => $this->zoneAvailability(),
            'vehicle_category_ids' => array_column($vehicles, 'id'),
            'vehicle_categories' => $vehicles,
            'form_schema' => [
                'version' => $version->formSchema->version,
                'fields' => array_map(static fn (array $field): array => self::field($field), (array) $snapshot['form_schema']['fields']),
            ],
        ];
    }

    /**
     * The serving zone's overrides when the list was filtered by position (null otherwise).
     *
     * @return array<string, mixed>|null
     */
    private function zoneAvailability(): ?array
    {
        $availability = $this->resource->getAttributes()['zone_availability'] ?? null;

        if (! is_array($availability)) {
            return null;
        }

        $overrides = (array) ($availability['overrides'] ?? []);

        return ['zone_id' => $availability['zone_id'], 'overrides' => $overrides === [] ? new stdClass : $overrides];
    }

    /**
     * Stored fields with empty maps emitted as objects.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private static function field(array $field): array
    {
        $field['label'] = TranslationMap::of((array) ($field['label'] ?? []));
        $field['help'] = isset($field['help']) ? TranslationMap::of((array) $field['help']) : null;
        $field['options'] = array_map(static fn (array $option): array => ['value' => $option['value'], 'label' => TranslationMap::of((array) $option['label'])], (array) ($field['options'] ?? []));

        return $field;
    }
}
