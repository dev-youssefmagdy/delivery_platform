<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\DTOs\Geography\CategoryAvailability;
use App\DTOs\Geography\ResolvedArea;
use App\Http\Resources\Api\V1\Catalog\TranslationMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /service-areas/resolve. Outside every active zone `in_service_area` is false and city/zone are null.
 *
 * @property ResolvedArea $resource
 */
class ResolvedAreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area = $this->resource;
        $city = $area->city;
        $zone = $area->zone;

        return [
            'in_service_area' => $area->inServiceArea(),
            'point' => $area->point->toArray(),
            'city' => $city === null ? null : [
                'id' => $city->id,
                'name' => TranslationMap::of($city->getTranslations('name')),
                'timezone' => $city->timezone,
                'center' => $city->center->toArray(),
            ],
            'zone' => $zone === null ? null : [
                'id' => $zone->id,
                'name' => TranslationMap::of($zone->getTranslations('name')),
                'restricted_pickup' => $zone->restricted_pickup,
            ],
            'local_time' => $area->localTime?->toIso8601String(),
            'categories' => array_map(static fn (CategoryAvailability $availability): array => $availability->toArray(), $area->categories),
        ];
    }
}
