<?php

namespace App\Repositories\Eloquent\Finance;

use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\RefundStatus;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\PaymentPositionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Payments that are not settled yet, read from `payments` / `payment_refunds` (replaces the neutral implementation of
 * ADR-115). Open = waiting for the gateway, flagged for reconciliation, cash not yet confirmed as collected, or a refund
 * that has not finished. The ledger-backed financial position adds this count to its wallet part.
 */
final class PaymentPositionRepository implements PaymentPositionRepositoryInterface
{
    public function openForCustomer(User $customer): int
    {
        return $this->openPayments(Payment::query()->where('user_id', $customer->id))
            + $this->openRefunds(PaymentRefund::query()->whereIn('payment_id', static fn ($sub) => $sub->select('id')->from('payments')->where('user_id', $customer->id)));
    }

    public function openForProvider(ProviderProfile $profile): int
    {
        return $this->openPayments(Payment::query()->where('provider_id', $profile->id))
            + $this->openRefunds(PaymentRefund::query()->whereIn('payment_id', static fn ($sub) => $sub->select('id')->from('payments')->where('provider_id', $profile->id)));
    }

    /**
     * @param Builder<Payment> $query
     */
    private function openPayments(Builder $query): int
    {
        return $query->whereIn('status', array_map(static fn (PaymentStatus $status): string => $status->value, PaymentStatus::open()))->count();
    }

    /**
     * @param Builder<PaymentRefund> $query
     */
    private function openRefunds(Builder $query): int
    {
        return $query->whereIn('status', array_map(static fn (RefundStatus $status): string => $status->value, RefundStatus::open()))->count();
    }
}
