<?php

namespace App\Http\Resources\Api\V1\Courier;

use App\Models\DeliveryAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DeliveryAttempt $resource
 */
final class DeliveryAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attempt = $this->resource;

        return [
            'id' => $attempt->uuid,
            'number' => $attempt->attempt_number,
            'outcome' => $attempt->outcome->value,
            'outcome_label' => $attempt->outcome->label(),
            'reason' => $attempt->reason,
            'has_evidence' => $attempt->evidence_proof_id !== null,
            'resolution' => $attempt->resolution?->value,
            'resolution_label' => $attempt->resolution?->label(),
            'resolved_at' => $attempt->resolved_at?->toIso8601ZuluString(),
            'created_at' => $attempt->created_at->toIso8601ZuluString(),
        ];
    }
}
