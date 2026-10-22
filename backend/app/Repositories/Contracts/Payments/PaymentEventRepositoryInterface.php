<?php

namespace App\Repositories\Contracts\Payments;

use App\Models\PaymentEvent;
use Illuminate\Support\Collection;

/**
 * Append-only timeline of payments: no update or delete method exists.
 */
interface PaymentEventRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function append(array $attributes): PaymentEvent;

    /**
     * @return Collection<int, PaymentEvent>
     */
    public function forPayment(int $paymentId): Collection;
}
