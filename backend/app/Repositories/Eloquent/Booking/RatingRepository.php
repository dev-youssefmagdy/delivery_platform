<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Reviews\ReviewFilterData;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
use App\Enums\Reviews\RatingReportStatus;
use App\Models\BookingRating;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RatingRepository implements RatingRepositoryInterface
{
    public function create(array $attributes): BookingRating
    {
        return BookingRating::query()->create($attributes);
    }

    public function findForSide(int $bookingId, string $raterType): ?BookingRating
    {
        return BookingRating::query()->where('booking_id', $bookingId)->where('rater_type', $raterType)->first();
    }

    public function visibleTotals(string $rateeType, int $rateeId): array
    {
        $row = BookingRating::query()
            ->where('ratee_type', $rateeType)
            ->where('ratee_id', $rateeId)
            ->where('moderation_status', ModerationStatus::Visible->value)
            ->selectRaw('count(*) as total, coalesce(sum(stars), 0) as stars')
            ->first();

        return ['count' => (int) ($row?->getAttribute('total') ?? 0), 'sum' => (int) ($row?->getAttribute('stars') ?? 0)];
    }

    public function storeAggregate(string $rateeType, int $rateeId, string $average, int $count): void
    {
        $table = $rateeType === ActorType::Provider->value ? 'provider_profiles' : 'users';

        DB::table($table)->where('id', $rateeId)->update(['rating_avg' => $average, 'rating_count' => $count]);
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingRating::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }

    public function datatableQueryForRatee(string $rateeType, int $rateeId): Builder
    {
        return BookingRating::query()
            ->join('bookings', 'bookings.id', '=', 'booking_ratings.booking_id')
            ->select('booking_ratings.*')
            ->selectRaw('bookings.code as booking_code, bookings.uuid as booking_uuid')
            ->where('booking_ratings.ratee_type', $rateeType)
            ->where('booking_ratings.ratee_id', $rateeId);
    }

    public function findByUuid(string $uuid): ?BookingRating
    {
        return BookingRating::query()->where('uuid', $uuid)->first();
    }

    public function lockById(int $id): ?BookingRating
    {
        return BookingRating::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function setModeration(BookingRating $rating, ModerationStatus $status): BookingRating
    {
        $rating->forceFill(['moderation_status' => $status, 'moderated_at' => now()])->save();

        return $rating;
    }

    public function syncReportsCount(BookingRating $rating): int
    {
        $count = $rating->reports()->where('status', RatingReportStatus::Open->value)->count();
        $rating->forceFill(['reports_count' => $count])->saveQuietly();

        return $count;
    }

    public function datatableQuery(ReviewFilterData $filters): Builder
    {
        return BookingRating::query()
            ->join('bookings', 'bookings.id', '=', 'booking_ratings.booking_id')
            ->select('booking_ratings.*')
            ->selectRaw('bookings.code as booking_code, bookings.uuid as booking_uuid')
            ->when($filters->starsMin !== null, static fn (Builder $q) => $q->where('booking_ratings.stars', '>=', (int) $filters->starsMin))
            ->when($filters->starsMax !== null, static fn (Builder $q) => $q->where('booking_ratings.stars', '<=', (int) $filters->starsMax))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('booking_ratings.moderation_status', array_map(static fn (ModerationStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->reportedOnly === true, static fn (Builder $q) => $q->where('booking_ratings.reports_count', '>', 0))
            ->when($filters->reportedOnly === false, static fn (Builder $q) => $q->where('booking_ratings.reports_count', 0))
            ->when($filters->rateeType !== null, static fn (Builder $q) => $q->where('booking_ratings.ratee_type', $filters->rateeType?->value))
            ->when($filters->bookingCode !== null, static fn (Builder $q) => $q->where('bookings.code', 'like', '%'.$filters->bookingCode.'%'))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('booking_ratings.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('booking_ratings.created_at', '<=', $filters->to?->endOfDay()));
    }
}
