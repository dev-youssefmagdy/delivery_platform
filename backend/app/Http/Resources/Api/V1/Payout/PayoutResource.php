<?php

namespace App\Http\Resources\Api\V1\Payout;

use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payout request as its provider sees it: amounts, status, the masked method snapshot, the transfer reference once paid and
 * the reason when it was rejected or failed. Reviewer identities and ledger links stay inside the panel.
 *
 * @property PayoutRequest $resource
 */
class PayoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payout = $this->resource;
        $amount = static fn (int $minor): array => ['amount_minor' => $minor, 'currency' => $payout->currency];

        return [
            'id' => $payout->uuid,
            'status' => ['code' => $payout->status->value, 'label' => $payout->status->label()],
            'amount' => $amount($payout->amount_minor),
            'fee' => $amount($payout->fee_minor),
            'net' => $amount($payout->net_minor),
            'method' => $payout->method_snapshot,
            'cancellable' => $payout->status->isCancellableByProvider(),
            'transfer_reference' => $payout->transfer_reference,
            'rejection_reason' => $payout->rejection_reason,
            'failure_reason' => $payout->failure_reason,
            'requested_at' => $payout->created_at->toIso8601ZuluString(),
            'approved_at' => $payout->approved_at?->toIso8601ZuluString(),
            'paid_at' => $payout->paid_at?->toIso8601ZuluString(),
            'closed_at' => $payout->closed_at?->toIso8601ZuluString(),
        ];
    }
}
