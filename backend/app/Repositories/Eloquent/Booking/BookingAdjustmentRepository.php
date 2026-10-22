<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\BookingAdjustmentFilterData;
use App\Enums\Booking\BookingAdjustmentStatus;
use App\Enums\Booking\BookingAdjustmentType;
use App\Models\BookingAdjustmentRequest;
use App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class BookingAdjustmentRepository implements BookingAdjustmentRepositoryInterface
{
    public function findByUuid(string $uuid): ?BookingAdjustmentRequest
    {
        return BookingAdjustmentRequest::query()->where('uuid', $uuid)->first();
    }

    public function lockByUuid(string $uuid): ?BookingAdjustmentRequest
    {
        return BookingAdjustmentRequest::query()->where('uuid', $uuid)->lockForUpdate()->first();
    }

    public function insert(array $attributes): BookingAdjustmentRequest
    {
        $request = new BookingAdjustmentRequest;
        $request->forceFill($attributes)->save();

        return $request;
    }

    public function update(BookingAdjustmentRequest $request, array $attributes): BookingAdjustmentRequest
    {
        $request->forceFill($attributes)->save();

        return $request;
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingAdjustmentRequest::query()->where('booking_id', $bookingId)->orderByDesc('id')->get();
    }

    public function approvedForBooking(int $bookingId): Collection
    {
        return BookingAdjustmentRequest::query()
            ->where('booking_id', $bookingId)
            ->where('status', BookingAdjustmentStatus::Approved->value)
            ->orderBy('decided_at')
            ->orderBy('id')
            ->get();
    }

    public function openForBooking(int $bookingId, ?BookingAdjustmentType $type = null): Collection
    {
        return BookingAdjustmentRequest::query()
            ->where('booking_id', $bookingId)
            ->where('status', BookingAdjustmentStatus::Pending->value)
            ->when($type !== null, static fn (Builder $query) => $query->where('type', $type?->value))
            ->lockForUpdate()
            ->get();
    }

    public function findByIdempotencyKey(int $bookingId, string $requesterType, int $requesterId, string $key): ?BookingAdjustmentRequest
    {
        return BookingAdjustmentRequest::query()
            ->where('booking_id', $bookingId)
            ->where('requester_type', $requesterType)
            ->where('requester_id', $requesterId)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function expiredPending(CarbonInterface $now, int $limit): Collection
    {
        return BookingAdjustmentRequest::query()
            ->where('status', BookingAdjustmentStatus::Pending->value)
            ->where('expires_at', '<=', $now)
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    public function pendingCount(): int
    {
        return BookingAdjustmentRequest::query()->where('status', BookingAdjustmentStatus::Pending->value)->count();
    }

    public function datatableQuery(BookingAdjustmentFilterData $filters): Builder
    {
        return BookingAdjustmentRequest::query()
            ->select('booking_adjustment_requests.*')
            ->with('booking:id,uuid,code,currency')
            ->when($filters->bookingId !== null, static fn (Builder $query) => $query->where('booking_adjustment_requests.booking_id', $filters->bookingId))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('booking_adjustment_requests.status', array_map(static fn (BookingAdjustmentStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->types !== [], static fn (Builder $query) => $query->whereIn('booking_adjustment_requests.type', array_map(static fn (BookingAdjustmentType $type): string => $type->value, $filters->types)))
            ->when($filters->requesters !== [], static fn (Builder $query) => $query->whereIn('booking_adjustment_requests.requester_type', array_map(static fn ($actor): string => $actor->value, $filters->requesters)))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('booking_adjustment_requests.uuid', $filters->search)
                ->orWhereIn('booking_adjustment_requests.booking_id', static fn ($sub) => $sub->select('id')->from('bookings')->where('code', $filters->search))))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('booking_adjustment_requests.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('booking_adjustment_requests.created_at', '<=', $filters->to?->endOfDay()));
    }
}
