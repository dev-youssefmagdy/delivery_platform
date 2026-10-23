<?php

namespace App\Http\Resources\Api\V1\Safety;

use App\Models\TrustedContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TrustedContact $resource
 */
class TrustedContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contact = $this->resource;

        return [
            'id' => $contact->uuid,
            'name' => $contact->name,
            'phone_masked' => $contact->maskedPhone(),
            'relationship' => $contact->relationship?->value,
            'notify_on_sos' => $contact->notify_on_sos,
            'share_trip' => $contact->share_trip,
            'share_location_on_sos' => $contact->share_location_on_sos,
            'consent_confirmed_at' => $contact->consent_confirmed_at->toIso8601ZuluString(),
        ];
    }
}
