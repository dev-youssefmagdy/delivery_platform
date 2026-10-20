<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property UserDevice $resource
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->resource->device_id,
            'platform' => $this->resource->platform->value,
            'app' => $this->resource->app->value,
            'app_version' => $this->resource->app_version,
            'has_push_token' => $this->resource->push_token !== null,
            'last_active_at' => $this->resource->last_active_at?->toIso8601ZuluString(),
        ];
    }
}
