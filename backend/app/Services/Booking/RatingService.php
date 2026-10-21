<?php

namespace App\Services\Booking;

use App\DTOs\Booking\RatingData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
use App\Enums\Reviews\RatingTag;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\BookingRating;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

/**
 * Ratings after a trip (SRS §6.7, §15): the customer rates the provider, and the provider rates the customer when the
 * setting allows. One rating per booking and side (a unique index backs the check), only for a finished trip, only inside
 * the rating window (`rating_window_hours` after completion). Tags must belong to the ratee's list. The public score is
 * recomputed from the rows by `RatingAggregationService`.
 */
final class RatingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingAggregationService $aggregation,
        private readonly SettingsService $settings,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `rating_not_allowed` (trip not finished), `rating_window_closed`, `rating_tag_invalid`
     * @throws ConflictException `rating_already_submitted`
     */
    public function rateProvider(User $customer, string $bookingUuid, RatingData $data): BookingRating
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if ($booking->provider_id === null) {
            throw BusinessRuleViolation::make('rating_not_allowed');
        }

        return $this->store($booking, ActorType::Customer, $customer->id, ActorType::Provider, $booking->provider_id, $data);
    }

    /**
     * @throws BusinessRuleViolation `rating_disabled` when providers may not rate customers
     */
    public function rateCustomer(ProviderProfile $provider, string $bookingUuid, RatingData $data): BookingRating
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if (! $this->settings->bool(SettingKey::RatingCustomerByProviderEnabled)) {
            throw BusinessRuleViolation::make('rating_disabled');
        }

        return $this->store($booking, ActorType::Provider, $provider->id, ActorType::Customer, $booking->customer_id, $data);
    }

    /**
     * Whether the rater can still rate this booking now (for `allowed_actions`).
     */
    public function canRate(Booking $booking, ActorType $rater, ?CarbonImmutable $now = null): bool
    {
        return $this->isRateable($booking, $now ?? CarbonImmutable::now())
            && $this->ratings->findForSide($booking->id, $rater->value) === null
            && ($rater !== ActorType::Provider || $this->settings->bool(SettingKey::RatingCustomerByProviderEnabled));
    }

    public function ratingOf(Booking $booking, ActorType $rater): ?BookingRating
    {
        return $this->ratings->findForSide($booking->id, $rater->value);
    }

    private function store(Booking $booking, ActorType $rater, int $raterId, ActorType $ratee, int $rateeId, RatingData $data): BookingRating
    {
        $now = CarbonImmutable::now();

        if (! $this->isRateable($booking, $now, throwing: true)) {
            throw BusinessRuleViolation::make('rating_not_allowed');
        }

        $tags = array_values(array_unique($data->tags));

        if (array_diff($tags, RatingTag::valuesForRatee($ratee)) !== []) {
            throw BusinessRuleViolation::make('rating_tag_invalid', 'tags');
        }

        try {
            $rating = $this->db->transaction(function () use ($booking, $rater, $raterId, $ratee, $rateeId, $data, $tags): BookingRating {
                if ($this->ratings->findForSide($booking->id, $rater->value) !== null) {
                    throw ConflictException::make('rating_already_submitted');
                }

                $rating = $this->ratings->create([
                    'booking_id' => $booking->id,
                    'rater_type' => $rater->value,
                    'rater_id' => $raterId,
                    'ratee_type' => $ratee->value,
                    'ratee_id' => $rateeId,
                    'stars' => $data->stars,
                    'tags' => $tags === [] ? null : $tags,
                    'comment' => $data->comment === null || trim($data->comment) === '' ? null : trim($data->comment),
                    'moderation_status' => ModerationStatus::Visible,
                ]);

                $this->aggregation->recompute($ratee->value, $rateeId);

                return $rating;
            });
        } catch (QueryException $exception) {
            // The unique (booking, side) index caught a concurrent duplicate.
            if (str_contains($exception->getMessage(), 'booking_ratings_one_per_side')) {
                throw ConflictException::make('rating_already_submitted');
            }

            throw $exception;
        }

        return $rating;
    }

    private function isRateable(Booking $booking, CarbonImmutable $now, bool $throwing = false): bool
    {
        if (! in_array($booking->status, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment], true) || $booking->completed_at === null) {
            return false;
        }

        $closesAt = $booking->completed_at->toImmutable()->addHours($this->settings->int(SettingKey::RatingWindowHours));

        if ($closesAt->lessThan($now)) {
            if ($throwing) {
                throw BusinessRuleViolation::make('rating_window_closed');
            }

            return false;
        }

        return true;
    }
}
