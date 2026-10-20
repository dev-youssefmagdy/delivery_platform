<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\DTOs\Provider\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One required (or optional) document of the driver's checklist and where it stands.
 *
 * @property ChecklistItem $resource
 */
class ChecklistItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource;

        return [
            'document_type' => new DocumentTypeResource($item->type),
            'mandatory' => $item->mandatory,
            'state' => $item->state?->value,
            'vehicle_id' => $item->vehicle?->uuid,
            'expires_at' => $item->expiresAt()?->toDateString(),
            'document' => $item->document === null ? null : new DocumentResource($item->document),
        ];
    }
}
