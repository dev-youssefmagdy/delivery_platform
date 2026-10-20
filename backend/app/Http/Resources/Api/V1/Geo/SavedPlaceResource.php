<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\Models\SavedPlace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SavedPlace $resource
 */
class SavedPlaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $place = $this->resource;

        return [
            'id' => $place->uuid,
            'label' => $place->label,
            'type' => $place->type->value,
            'lat' => $place->point->toArray()['lat'],
            'lng' => $place->point->toArray()['lng'],
            'address' => $place->address,
            'created_at' => $place->created_at->toIso8601ZuluString(),
        ];
    }
}
