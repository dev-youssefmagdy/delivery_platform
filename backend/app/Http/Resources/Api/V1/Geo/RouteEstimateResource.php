<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\DTOs\Geography\RouteEstimate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /routes/estimate: distance, duration and polyline only (pricing arrives with P2.3).
 *
 * @property RouteEstimate $resource
 */
class RouteEstimateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area = $this->resource->origin;

        return $this->resource->route->toArray() + [
            'origin_zone_id' => $area->zone?->id,
            'origin_city_id' => $area->city?->id,
        ];
    }
}
