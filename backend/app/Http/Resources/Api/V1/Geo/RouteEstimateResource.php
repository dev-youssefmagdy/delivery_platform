<?php

namespace App\Http\Resources\Api\V1\Geo;

use App\DTOs\Geography\RouteEstimate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /routes/estimate: distance, duration, polyline and, for a requested service category, the reference price guidance.
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

        $pricing = $this->resource->pricing;

        return $this->resource->route->toArray() + [
            'origin_zone_id' => $area->zone?->id,
            'origin_city_id' => $area->city?->id,
            'price_guidance' => $pricing?->toApi(),
            'price_guidance_unavailable_reason' => $pricing?->unavailableReason,
        ];
    }
}
