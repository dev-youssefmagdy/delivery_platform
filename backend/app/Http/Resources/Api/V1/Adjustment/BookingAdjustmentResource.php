<?php

namespace App\Http\Resources\Api\V1\Adjustment;

use App\Enums\Common\ActorType;
use App\Models\BookingAdjustmentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A fare adjustment as the two apps see it. `requested_by_me` and `can_answer` are computed for the calling side so the
 * app shows Approve / Reject only to the party that must decide, while the request is pending and not expired.
 *
 * @property BookingAdjustmentRequest $resource
 */
class BookingAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $adjustment = $this->resource;
        $me = $request->routeIs('api.v1.provider.*') ? ActorType::Provider : ActorType::Customer;
        $mine = $adjustment->requester_type === $me->value;
        $open = $adjustment->status->isOpen() && $adjustment->expires_at->isFuture();

        return [
            'uuid' => $adjustment->uuid,
            'type' => $adjustment->type->value,
            'type_label' => $adjustment->type->label(),
            'status' => $adjustment->status->value,
            'status_label' => $adjustment->status->label(),
            'requester' => $adjustment->requester_type,
            'requested_by_me' => $mine,
            'can_answer' => $open && ! $mine,
            'can_withdraw' => $open && $mine,
            'amount' => ['amount_minor' => $adjustment->amount_minor, 'currency' => $adjustment->currency],
            'old_total' => ['amount_minor' => $adjustment->old_total_minor, 'currency' => $adjustment->currency],
            'new_total' => ['amount_minor' => $adjustment->new_total_minor, 'currency' => $adjustment->currency],
            'reason' => $adjustment->reason,
            'details' => $adjustment->details,
            'evidence' => $adjustment->evidence,
            'expires_at' => $adjustment->expires_at->toIso8601ZuluString(),
            'decided_at' => $adjustment->decided_at?->toIso8601ZuluString(),
            'decision_note' => $adjustment->decision_note,
            'created_at' => $adjustment->created_at->toIso8601ZuluString(),
        ];
    }
}
