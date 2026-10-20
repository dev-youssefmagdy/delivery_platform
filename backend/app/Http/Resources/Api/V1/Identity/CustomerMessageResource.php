<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\Models\CustomerMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CustomerMessage $resource
 */
class CustomerMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'read' => $this->resource->read_at !== null,
            'sent_at' => $this->resource->created_at?->toIso8601ZuluString(),
        ];
    }
}
