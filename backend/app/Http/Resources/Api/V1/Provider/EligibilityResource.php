<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Typed reasons a provider cannot work (the app maps each code to text).
 *
 * @property EligibilityResult $resource
 */
class EligibilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'eligible' => $this->resource->isEligible(),
            'reasons' => array_map(static fn (EligibilityIssue $issue): array => [
                'reason' => $issue->reason->value,
                'reference' => $issue->reference,
            ], $this->resource->issues),
        ];
    }
}
