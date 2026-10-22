<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\DeliveryAttempt;
use Illuminate\Support\Collection;

interface DeliveryAttemptRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): DeliveryAttempt;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(DeliveryAttempt $attempt, array $attributes): DeliveryAttempt;

    /**
     * @return Collection<int, DeliveryAttempt>
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * Row lock: call inside a transaction.
     */
    public function lockInBooking(int $bookingId, string $uuid): ?DeliveryAttempt;

    public function latestFor(int $bookingId): ?DeliveryAttempt;

    /**
     * Ids of unresolved attempts reported before `$before` (the stale-attempt sweep).
     *
     * @return list<int> booking ids
     */
    public function bookingIdsWithPendingBefore(\Carbon\CarbonInterface $before, int $limit): array;

    /**
     * Removes the free text of the customer's bookings' attempts (account deletion). Outcomes and times stay.
     */
    public function scrubForCustomer(int $customerId): int;
}
