<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\DTOs\Identity\ConsentStatus;
use App\Models\PolicyVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ConsentStatus $resource
 */
class ConsentStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = static fn (PolicyVersion $policy): array => [
            'id' => $policy->uuid,
            'type' => $policy->type->value,
            'version' => $policy->version,
        ];

        return [
            'required' => array_map($summary, $this->resource->required),
            'pending' => array_map($summary, $this->resource->pending),
            'accepted' => array_map(static fn ($consent): array => [
                'policy_id' => $consent->policyVersion->uuid,
                'type' => $consent->policyVersion->type->value,
                'version' => $consent->policyVersion->version,
                'locale' => $consent->locale,
                'accepted_at' => $consent->accepted_at->toIso8601ZuluString(),
            ], $this->resource->accepted),
        ];
    }
}
