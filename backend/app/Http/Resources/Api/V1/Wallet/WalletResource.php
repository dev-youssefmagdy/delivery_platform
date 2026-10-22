<?php

namespace App\Http\Resources\Api\V1\Wallet;

use App\Models\LedgerAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A wallet balance (cached ledger balance, in the account's natural sign).
 *
 * @property LedgerAccount $resource
 */
class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid,
            'currency' => $this->resource->currency,
            'balance' => ['amount_minor' => $this->resource->balance_minor, 'currency' => $this->resource->currency],
        ];
    }
}
