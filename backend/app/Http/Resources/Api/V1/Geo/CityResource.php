<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\Http\Resources\Api\V1\Catalog\TranslationMap;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property City $resource
 */
class CityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => TranslationMap::of($this->resource->getTranslations('name')),
            'timezone' => $this->resource->timezone,
            'center' => $this->resource->center->toArray(),
        ];
    }
}
