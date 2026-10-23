<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Reviews\ReviewFilterData;
use App\Enums\Reviews\ModerationStatus;
use App\Models\BookingRating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * Both sides' ratings of the booking (admin show page).
     *
     * @return Collection<int, BookingRating>
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * Ratings received by the customer or provider, with the booking code (ratings tabs of the admin).
     *
     * @return Builder<BookingRating>
     */
    public function datatableQueryForRatee(string $rateeType, int $rateeId): Builder;

    public function findByUuid(string $uuid): ?BookingRating;

    /**
     * Row lock for a moderation change (the whole decision runs in one transaction).
     */
    public function lockById(int $id): ?BookingRating;

    /**
     * Changes the visibility only. The text, stars and tags are never written here (SRS §16.12).
     */
    public function setModeration(BookingRating $rating, ModerationStatus $status): BookingRating;

    /**
     * Recounts the open reports of the rating from the rows and stores the number (idempotent).
     */
    public function syncReportsCount(BookingRating $rating): int;

    /**
     * Reviews listing of the admin (Appendix C filters), with booking code and the rater's and ratee's display names resolved by the table.
     *
     * @return Builder<BookingRating>
     */
    public function datatableQuery(ReviewFilterData $filters): Builder;
}
