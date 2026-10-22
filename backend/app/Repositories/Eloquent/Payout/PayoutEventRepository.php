<?php

namespace App\Repositories\Eloquent\Payout;

use App\Models\PayoutEvent;
use App\Repositories\Contracts\Payout\PayoutEventRepositoryInterface;
use Illuminate\Support\Collection;

final class PayoutEventRepository implements PayoutEventRepositoryInterface
{
    public function insert(array $attributes): PayoutEvent
    {
        $event = new PayoutEvent;
        $event->forceFill($attributes)->save();

        return $event;
    }

    public function forPayout(int $payoutId): Collection
    {
        return PayoutEvent::query()->where('payout_request_id', $payoutId)->orderBy('id')->get();
    }
}
