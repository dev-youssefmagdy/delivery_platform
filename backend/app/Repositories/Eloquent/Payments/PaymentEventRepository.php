<?php

namespace App\Repositories\Eloquent\Payments;

use App\Models\PaymentEvent;
use App\Repositories\Contracts\Payments\PaymentEventRepositoryInterface;
use Illuminate\Support\Collection;

final class PaymentEventRepository implements PaymentEventRepositoryInterface
{
    public function append(array $attributes): PaymentEvent
    {
        return PaymentEvent::query()->create($attributes);
    }

    public function forPayment(int $paymentId): Collection
    {
        return PaymentEvent::query()->where('payment_id', $paymentId)->orderBy('id')->get();
    }
}
