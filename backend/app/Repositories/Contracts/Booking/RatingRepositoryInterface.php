<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingRating;

interface RatingRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): BookingRating;

    public function findForSide(int $bookingId, string $raterType): ?BookingRating;

    /**
     * Number and star sum of the ratee's visible ratings (moderated ones do not count).
     *
     * @return array{count: int, sum: int}
     */
    public function visibleTotals(string $rateeType, int $rateeId): array;

    /**
     * Writes the computed aggregate onto the rated profile (`rating_avg`, `rating_count`).
     */
    public function storeAggregate(string $rateeType, int $rateeId, string $average, int $count): void;
}
