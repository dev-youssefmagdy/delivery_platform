<?php

namespace App\Http\Resources\Api\V1\Wallet;

use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of a wallet statement: the change it made (positive = money in), the balance right after and what it was for. The
 * entry's internal meta and counter-accounts are not exposed.
 *
 * @property JournalLine $resource
 */
class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $line = $this->resource;
        $signed = $line->side === $line->account->type->normalSide() ? $line->amount_minor : -$line->amount_minor;

        return [
            'id' => $line->id,
            'entry_id' => $line->entry->uuid,
            'type' => ['code' => $line->entry->type->value, 'label' => $line->entry->type->label()],
            'description' => __('admin.'.$line->entry->description_key),
            'change' => ['amount_minor' => $signed, 'currency' => $line->currency],
            'balance_after' => ['amount_minor' => $line->balance_after_minor, 'currency' => $line->currency],
            'posted_at' => $line->entry->posted_at->toIso8601ZuluString(),
        ];
    }
}
