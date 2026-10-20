<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property VehicleCategory $resource
 */
class VehicleCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;

        return [
            'id' => $vehicle->id,
            'code' => $vehicle->code,
            'name' => TranslationMap::of($vehicle->getTranslations('name')),
            'description' => TranslationMap::of($vehicle->getTranslations('description')),
            'icon' => $vehicle->icon,
            'map_marker' => $vehicle->map_marker,
            'passenger_capacity' => $vehicle->passenger_capacity,
            'luggage_capacity' => $vehicle->luggage_capacity,
            'max_payload_kg' => $vehicle->max_payload_kg,
            'max_volume_m3' => $vehicle->max_volume_m3,
            'sort_order' => $vehicle->sort_order,
        ];
    }
}
