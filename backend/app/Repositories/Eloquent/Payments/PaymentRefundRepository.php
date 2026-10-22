<?php

namespace App\Repositories\Eloquent\Payments;

use App\DTOs\Payments\RefundFilterData;
use App\Enums\Payments\RefundStatus;
use App\Models\PaymentRefund;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PaymentRefundRepository implements PaymentRefundRepositoryInterface
{
    public function find(int $id): ?PaymentRefund
    {
        return PaymentRefund::query()->find($id);
    }

    public function findByUuid(string $uuid): ?PaymentRefund
    {
        return PaymentRefund::query()->where('uuid', $uuid)->first();
    }

    public function lockById(int $id): ?PaymentRefund
    {
        return PaymentRefund::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findByIdempotencyKey(string $key): ?PaymentRefund
    {
        return PaymentRefund::query()->where('idempotency_key', $key)->first();
    }

    public function findByGatewayReference(int $paymentId, string $reference): ?PaymentRefund
    {
        return PaymentRefund::query()->where('payment_id', $paymentId)->where('gateway_reference', $reference)->first();
    }

    public function insert(array $attributes): PaymentRefund
    {
        $refund = new PaymentRefund;
        $refund->forceFill($attributes)->save();

        return $refund;
    }

    public function update(PaymentRefund $refund, array $attributes): PaymentRefund
    {
        $refund->forceFill($attributes)->save();

        return $refund;
    }

    public function reservedMinor(int $paymentId): int
    {
        return (int) PaymentRefund::query()
            ->where('payment_id', $paymentId)
            ->whereIn('status', array_map(static fn (RefundStatus $status): string => $status->value, RefundStatus::reserving()))
            ->sum('amount_minor');
    }

    public function processingForPayment(int $paymentId): Collection
    {
        return PaymentRefund::query()->where('payment_id', $paymentId)->where('status', RefundStatus::Processing->value)->orderBy('id')->get();
    }

    public function awaitingApprovalCount(): int
    {
        return PaymentRefund::query()->where('status', RefundStatus::PendingApproval->value)->count();
    }

    public function datatableQuery(RefundFilterData $filters): Builder
    {
        return PaymentRefund::query()
            ->select('payment_refunds.*')
            ->with(['payment:id,uuid,booking_id,kind', 'payment.booking:id,uuid,code', 'requestedBy:id,name', 'decidedBy:id,name'])
            ->when($filters->paymentId !== null, static fn (Builder $query) => $query->where('payment_refunds.payment_id', $filters->paymentId))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('payment_refunds.status', array_map(static fn (RefundStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->dualApproval !== null, static fn (Builder $query) => $query->where('payment_refunds.requires_dual_approval', $filters->dualApproval))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('payment_refunds.uuid', $filters->search)
                ->orWhereIn('payment_refunds.payment_id', static fn ($sub) => $sub->select('payments.id')->from('payments')
                    ->where('payments.uuid', $filters->search)
                    ->orWhereIn('payments.booking_id', static fn ($bookings) => $bookings->select('id')->from('bookings')->where('code', $filters->search)))))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('payment_refunds.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('payment_refunds.created_at', '<=', $filters->to?->endOfDay()));
    }
}
