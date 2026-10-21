<?php

namespace App\Repositories\Eloquent\Booking;

use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
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
}
