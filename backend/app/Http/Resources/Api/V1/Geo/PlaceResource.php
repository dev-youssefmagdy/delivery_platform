<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\DTOs\Geography\PlaceData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A geocoder result. `precision` must drive the UI: only `exact` may be shown as a precise address.
 *
 * @property PlaceData $resource
 */
class PlaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
