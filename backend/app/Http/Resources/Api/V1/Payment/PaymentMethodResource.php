<?php

namespace App\Http\Resources\Api\V1\Payment;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A saved method: brand and last four digits only. The gateway token never leaves the server.
 *
 * @property PaymentMethod $resource
 */
class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $method = $this->resource;

        return [
            'id' => $method->uuid,
            'type' => $method->type->value,
            'brand' => $method->brand,
            'last4' => $method->last4,
            'is_default' => (bool) $method->is_default,
            'status' => $method->status,
            'created_at' => $method->created_at->toIso8601ZuluString(),
        ];
    }
}
