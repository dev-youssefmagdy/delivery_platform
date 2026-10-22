<?php

namespace App\Repositories\Contracts\Payments;

use App\DTOs\Payments\RefundFilterData;
use App\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface PaymentRefundRepositoryInterface
{
    public function find(int $id): ?PaymentRefund;

    public function findByUuid(string $uuid): ?PaymentRefund;

    public function lockById(int $id): ?PaymentRefund;

    public function findByIdempotencyKey(string $key): ?PaymentRefund;

    public function findByGatewayReference(int $paymentId, string $reference): ?PaymentRefund;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): PaymentRefund;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(PaymentRefund $refund, array $attributes): PaymentRefund;

    /**
     * Sum of refunds that already took or still hold part of a payment (pending, approved, processing, completed).
     */
    public function reservedMinor(int $paymentId): int;

    /**
     * Refunds sent to the gateway and still waiting for its confirmation.
     *
     * @return Collection<int, PaymentRefund>
     */
    public function processingForPayment(int $paymentId): Collection;

    public function awaitingApprovalCount(): int;

    /**
     * @return Builder<PaymentRefund>
     */
    public function datatableQuery(RefundFilterData $filters): Builder;
}
