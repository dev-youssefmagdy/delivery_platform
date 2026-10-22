<?php

namespace App\Repositories\Contracts\Payments;

use App\DTOs\Payments\PaymentFilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentKind;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * All queries over `payments`. `status` is written only by PaymentTransitionService through `update()`.
 */
interface PaymentRepositoryInterface
{
    public function find(int $id): ?Payment;

    public function findByUuid(string $uuid): ?Payment;

    /**
     * Row lock: every state change of a payment serialises here. Inside a transaction.
     */
    public function lockById(int $id): ?Payment;

    public function lockByGatewayReference(PaymentGatewayName $gateway, string $reference): ?Payment;

    public function findByGatewayReference(PaymentGatewayName $gateway, string $reference): ?Payment;

    /**
     * By the processor's own order id kept in `meta.gateway_order_id` (callbacks that do not echo our reference).
     */
    public function findByGatewayOrderId(string $orderId): ?Payment;

    /**
     * @param array<string, mixed> $attributes including `status`
     */
    public function insert(array $attributes): Payment;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Payment $payment, array $attributes): Payment;

    /**
     * The payments of a booking, newest first.
     *
     * @return Collection<int, Payment>
     */
    public function forBooking(int $bookingId, ?PaymentKind $kind = null): Collection;

    /**
     * The attempt still in flight (pending / requires action / authorized) of a booking and kind, if any.
     */
    public function openAttempt(int $bookingId, PaymentKind $kind): ?Payment;

    /**
     * Number of attempts already made for the booking and kind.
     */
    public function attemptCount(int $bookingId, PaymentKind $kind): int;

    /**
     * Sum of paid (collected, not cancelled) tips of a booking.
     */
    public function tipTotalMinor(int $bookingId): int;

    /**
     * Online payments still waiting for the gateway that were created before $cutoff.
     *
     * @return Collection<int, Payment>
     */
    public function awaitingGatewayCreatedBefore(CarbonInterface $cutoff, int $limit): Collection;

    /**
     * Online payments created in a window that have a gateway reference.
     *
     * @return Collection<int, Payment>
     */
    public function onlineInWindow(CarbonInterface $from, CarbonInterface $to): Collection;

    /**
     * @return Builder<Payment>
     */
    public function datatableQuery(PaymentFilterData $filters): Builder;
}
