<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\BookingAdjustmentFilterData;
use App\Enums\Booking\BookingAdjustmentType;
use App\Models\BookingAdjustmentRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface BookingAdjustmentRepositoryInterface
{
    public function findByUuid(string $uuid): ?BookingAdjustmentRequest;

    public function lockByUuid(string $uuid): ?BookingAdjustmentRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): BookingAdjustmentRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(BookingAdjustmentRequest $request, array $attributes): BookingAdjustmentRequest;

    /**
     * Every adjustment of a booking, newest first.
     *
     * @return Collection<int, BookingAdjustmentRequest>
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * Approved adjustments of a booking in the order they were decided (the receipt lines).
     *
     * @return Collection<int, BookingAdjustmentRequest>
     */
    public function approvedForBooking(int $bookingId): Collection;

    /**
     * Pending requests of a booking (optionally of one type), locked: used to cap open requests.
     *
     * @return Collection<int, BookingAdjustmentRequest>
     */
    public function openForBooking(int $bookingId, ?BookingAdjustmentType $type = null): Collection;

    public function findByIdempotencyKey(int $bookingId, string $requesterType, int $requesterId, string $key): ?BookingAdjustmentRequest;

    /**
     * Pending requests whose time ran out, oldest first.
     *
     * @return Collection<int, BookingAdjustmentRequest>
     */
    public function expiredPending(CarbonInterface $now, int $limit): Collection;

    public function pendingCount(): int;

    /**
     * @return Builder<BookingAdjustmentRequest>
     */
    public function datatableQuery(BookingAdjustmentFilterData $filters): Builder;
}
