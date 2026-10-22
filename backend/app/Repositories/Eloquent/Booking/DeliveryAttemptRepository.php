<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\DeliveryAttempt;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DeliveryAttemptRepository implements DeliveryAttemptRepositoryInterface
{
    public function create(array $attributes): DeliveryAttempt
    {
        return DeliveryAttempt::query()->create($attributes);
    }

    public function update(DeliveryAttempt $attempt, array $attributes): DeliveryAttempt
    {
        $attempt->forceFill($attributes)->save();

        return $attempt;
    }

    public function forBooking(int $bookingId): Collection
    {
        return DeliveryAttempt::query()->where('booking_id', $bookingId)->orderBy('attempt_number')->get();
    }

    public function lockInBooking(int $bookingId, string $uuid): ?DeliveryAttempt
    {
        return DeliveryAttempt::query()->where('booking_id', $bookingId)->where('uuid', $uuid)->lockForUpdate()->first();
    }

    public function latestFor(int $bookingId): ?DeliveryAttempt
    {
        return DeliveryAttempt::query()->where('booking_id', $bookingId)->orderByDesc('attempt_number')->first();
    }

    public function bookingIdsWithPendingBefore(\Carbon\CarbonInterface $before, int $limit): array
    {
        return array_map('intval', DeliveryAttempt::query()->toBase()
            ->whereNull('resolution')->where('created_at', '<', $before)
            ->orderBy('id')->limit($limit)->distinct()->pluck('booking_id')->all());
    }

    public function scrubForCustomer(int $customerId): int
    {
        return DB::table('delivery_attempts')
            ->whereIn('booking_id', DB::table('bookings')->where('customer_id', $customerId)->select('id'))
            ->update(['reason' => null, 'updated_at' => now()]);
    }
}
