<?php

namespace App\Http\Resources\Api\V1\Payout;

use App\Models\ProviderPayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payout method as its owner sees it: type, label, last digits and whether finance verified it. The encrypted details
 * (holder, bank, full number) never leave the server.
 *
 * @property ProviderPayoutMethod $resource
 */
class PayoutMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $method = $this->resource;

        return [
            'id' => $method->uuid,
            'type' => ['code' => $method->type->value, 'label' => $method->type->label()],
            'label' => $method->label,
            'last4' => $method->last4,
            'is_default' => $method->is_default,
            'verified' => $method->isVerified(),
            'verified_at' => $method->verified_at?->toIso8601ZuluString(),
            'created_at' => $method->created_at?->toIso8601ZuluString(),
        ];
    }
}
