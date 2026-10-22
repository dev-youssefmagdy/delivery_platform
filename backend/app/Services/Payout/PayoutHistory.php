<?php

namespace App\Services\Payout;

use App\Enums\Payout\PayoutStatus;
use App\Models\Admin;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutEventRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Writes the append-only timeline of a payout (one row per status change, with who did it).
 */
final class PayoutHistory
{
    public function __construct(private readonly PayoutEventRepositoryInterface $events) {}

    /**
     * @param array<string, mixed> $meta
     */
    public function record(PayoutRequest $payout, ?PayoutStatus $from, PayoutStatus $to, ?Authenticatable $actor, ?string $note = null, array $meta = []): void
    {
        $this->events->insert([
            'payout_request_id' => $payout->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actor instanceof Admin ? 'admin' : ($actor === null ? 'system' : 'provider'),
            'actor_id' => $actor?->getAuthIdentifier(),
            'note' => $note,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
