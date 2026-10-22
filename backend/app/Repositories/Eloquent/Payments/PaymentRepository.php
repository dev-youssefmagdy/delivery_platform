<?php

namespace App\Repositories\Eloquent\Payments;

use App\DTOs\Payments\PaymentFilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function find(int $id): ?Payment
    {
        return Payment::query()->find($id);
    }

    public function findByUuid(string $uuid): ?Payment
    {
        return Payment::query()->where('uuid', $uuid)->first();
    }

    public function lockById(int $id): ?Payment
    {
        return Payment::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function lockByGatewayReference(PaymentGatewayName $gateway, string $reference): ?Payment
    {
        return Payment::query()->where('gateway', $gateway->value)->where('gateway_reference', $reference)->lockForUpdate()->first();
    }

    public function findByGatewayReference(PaymentGatewayName $gateway, string $reference): ?Payment
    {
        return Payment::query()->where('gateway', $gateway->value)->where('gateway_reference', $reference)->first();
    }

    public function findByGatewayOrderId(string $orderId): ?Payment
    {
        return Payment::query()->where('meta->gateway_order_id', $orderId)->first();
    }

    public function insert(array $attributes): Payment
    {
        $payment = new Payment;
        $payment->forceFill($attributes)->save();

        return $payment;
    }

    public function update(Payment $payment, array $attributes): Payment
    {
        $payment->forceFill($attributes)->save();

        return $payment;
    }

    public function forBooking(int $bookingId, ?PaymentKind $kind = null): Collection
    {
        return Payment::query()
            ->where('booking_id', $bookingId)
            ->when($kind !== null, static fn (Builder $query) => $query->where('kind', $kind?->value))
            ->orderByDesc('id')
            ->get();
    }

    public function openAttempt(int $bookingId, PaymentKind $kind): ?Payment
    {
        return Payment::query()
            ->where('booking_id', $bookingId)
            ->where('kind', $kind->value)
            ->whereIn('status', array_map(static fn (PaymentStatus $status): string => $status->value, PaymentStatus::awaitingGateway()))
            ->orderByDesc('id')
            ->first();
    }

    public function attemptCount(int $bookingId, PaymentKind $kind): int
    {
        return Payment::query()->where('booking_id', $bookingId)->where('kind', $kind->value)->count();
    }

    public function tipTotalMinor(int $bookingId): int
    {
        return (int) Payment::query()
            ->where('booking_id', $bookingId)
            ->where('kind', PaymentKind::Tip->value)
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value])
            ->sum('amount_minor');
    }

    public function awaitingGatewayCreatedBefore(CarbonInterface $cutoff, int $limit): Collection
    {
        return Payment::query()
            ->whereNotNull('gateway_reference')
            ->whereIn('status', array_map(static fn (PaymentStatus $status): string => $status->value, PaymentStatus::awaitingGateway()))
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function onlineInWindow(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Payment::query()
            ->whereNotNull('gateway')
            ->whereNotNull('gateway_reference')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('id')
            ->get();
    }

    public function datatableQuery(PaymentFilterData $filters): Builder
    {
        $values = static fn (array $cases): array => array_map(static fn ($case): string => $case->value, $cases);

        return Payment::query()
            ->select('payments.*')
            ->with(['booking:id,uuid,code', 'user:id,name,phone_e164'])
            ->when($filters->bookingId !== null, static fn (Builder $query) => $query->where('payments.booking_id', $filters->bookingId))
            ->when($filters->userId !== null, static fn (Builder $query) => $query->where('payments.user_id', $filters->userId))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('payments.status', $values($filters->statuses)))
            ->when($filters->kinds !== [], static fn (Builder $query) => $query->whereIn('payments.kind', $values($filters->kinds)))
            ->when($filters->methods !== [], static fn (Builder $query) => $query->whereIn('payments.method_type', $values($filters->methods)))
            ->when($filters->gateways !== [], static fn (Builder $query) => $query->whereIn('payments.gateway', $values($filters->gateways)))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('payments.uuid', $filters->search)
                ->orWhere('payments.gateway_reference', $filters->search)
                ->orWhereIn('payments.booking_id', static fn ($sub) => $sub->select('id')->from('bookings')->where('code', $filters->search))))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('payments.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('payments.created_at', '<=', $filters->to?->endOfDay()))
            ->when($filters->minAmountMinor !== null, static fn (Builder $query) => $query->where('payments.amount_minor', '>=', $filters->minAmountMinor))
            ->when($filters->maxAmountMinor !== null, static fn (Builder $query) => $query->where('payments.amount_minor', '<=', $filters->maxAmountMinor));
    }
}
