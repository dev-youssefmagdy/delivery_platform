<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AccountDeletionRequest $resource
 */
class DeletionRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid,
            'status' => $this->resource->status->value,
            'requested_at' => $this->resource->requested_at->toIso8601ZuluString(),
            'processed_at' => $this->resource->processed_at?->toIso8601ZuluString(),
        ];
    }
}
