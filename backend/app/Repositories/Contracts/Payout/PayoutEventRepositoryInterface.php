<?php

namespace App\Repositories\Contracts\Payout;

use App\Models\PayoutEvent;
use Illuminate\Support\Collection;

/**
 * Append-only history of payouts: there is no update or delete.
 */
interface PayoutEventRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): PayoutEvent;

    /**
     * @return Collection<int, PayoutEvent>
     */
    public function forPayout(int $payoutId): Collection;
}
