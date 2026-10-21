<?php

namespace App\Services\Offers;

use App\DTOs\Booking\OfferSelectionResult;
use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferStatus;
use App\Enums\Common\ActorType;
use App\Enums\Provider\EligibilityScope;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Booking\BookingTransitionService;
use App\Services\Provider\ProviderEligibilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * THE critical section of the product: the customer chooses one offer (SRS §8.2, Plan I1, I2, I9, ADR-94).
 *
 * One transaction, always in the same lock order (booking, then offers):
 * 1. lock the booking row FOR UPDATE and re-check status and `selected_offer_id IS NULL`;
 * 2. lock the offer and check it is active, unexpired (checked here, never left to the expiry job) and that its
 *    provider is still eligible in the Working scope;
 * 3. conditional update of the offer (`WHERE status active AND expires_at > now`, one row expected) which also takes
 *    the unique winner marker, so the database itself refuses a second winner;
 * 4. `BookingTransitionService::transition(ProviderSelected)` writes the booking (the only writer of the status);
 * 5. agreed fare, scope and version are copied onto the booking, the trip PIN is generated;
 * 6. every other active offer ends as `lost_booking_assigned`.
 * Events leave after commit. A booking that is already assigned answers 409 `booking_already_assigned`, an expired
 * or closed offer 409 `offer_expired` / `offer_not_active`. Choosing the offer that already won is a replay, not an error.
 */
final class OfferSelectionService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly ProviderEligibilityService $eligibility,
        private readonly BookingTransitionService $transitions,
        private readonly OfferCloser $closer,
        private readonly OfferSettlement $settlement,
        private readonly OfferNotifier $notifier,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ConflictException `booking_already_assigned`, `offer_expired`, `offer_not_active`, `offer_provider_unavailable`
     */
    public function accept(User $customer, string $bookingUuid, string $offerUuid, ?string $idempotencyKey = null): OfferSelectionResult
    {
        [$booking, $offerRow] = $this->resolve($customer, $bookingUuid, $offerUuid);

        $outcome = $this->db->transaction(function () use ($customer, $booking, $offerRow, $idempotencyKey): OfferSelectionResult|ConflictException {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if ($locked->selected_offer_id === $offerRow->id) {
                return new OfferSelectionResult($locked, $offerRow->refresh(), true);
            }

            if (! $locked->status->isSearching() || $locked->selected_offer_id !== null || $locked->provider_id !== null) {
                throw ConflictException::make('booking_already_assigned', context: ['booking_status' => $locked->status->value]);
            }

            $offer = $this->offers->lockOffer($offerRow->id);

            if (! $offer instanceof BookingOffer || $offer->booking_id !== $locked->id) {
                throw (new ModelNotFoundException)->setModel(BookingOffer::class);
            }

            $now = CarbonImmutable::now();

            if (! $offer->status->isActive()) {
                throw ConflictException::make($offer->status === OfferStatus::Expired ? 'offer_expired' : 'offer_not_active', context: ['offer_status' => $offer->status->value]);
            }

            if ($offer->expires_at->lessThanOrEqualTo($now)) {
                throw ConflictException::make('offer_expired', context: ['offer_status' => $offer->status->value]);
            }

            if (! $this->providerCanWork($offer, $locked)) {
                $this->closer->close($locked, collect([$offer]), OfferStatus::InvalidatedProviderUnavailable, 'provider_unavailable', $now);
                $this->settlement->afterOfferLoss($locked, $now);

                return ConflictException::make('offer_provider_unavailable', context: ['offer_status' => OfferStatus::InvalidatedProviderUnavailable->value]);
            }

            if (! $this->offers->accept($offer, $now)) {
                throw ConflictException::make('offer_expired', context: ['offer_status' => $offer->status->value]);
            }

            $offer->loadMissing('currentVersion');
            $version = $offer->currentVersion;

            if ($version === null) {
                throw ConflictException::make('offer_not_active');
            }

            $selected = $this->transitions->transition($locked, BookingStatus::ProviderSelected, new TransitionContext(
                actorType: ActorType::Customer,
                actorId: $customer->id,
                reason: 'offer_accepted',
                metadata: array_filter([
                    'offer_id' => $offer->uuid,
                    'offer_version' => $version->version,
                    'amount_minor' => $version->amount_minor,
                    'idempotency_key' => $idempotencyKey,
                ], static fn (mixed $value): bool => $value !== null),
                providerId: $offer->provider_profile_id,
                offerId: $offer->id,
                at: $now,
            ));

            $pin = $this->newPin();
            $selected = $this->bookings->forceUpdate($selected, [
                'agreed_fare_minor' => $version->amount_minor,
                'agreed_offer_version_id' => $version->id,
                'agreed_scope' => $version->scope,
                'provider_selected_at' => $now,
                'pin_hash' => Hash::make($pin),
                'pin_encrypted' => Crypt::encryptString($pin),
            ]);

            $this->closer->close($selected, $this->offers->lockActiveForBooking($selected->id), OfferStatus::LostBookingAssigned, 'lost_booking_assigned', $now);

            $offer->refresh();
            $this->notifier->selected($selected, $offer);

            return new OfferSelectionResult($selected, $offer, false);
        });

        if ($outcome instanceof ConflictException) {
            throw $outcome;
        }

        return $outcome;
    }

    /**
     * The customer declines one offer. The provider is told; if it was the last active offer and the search is still
     * open, the booking returns to searching.
     */
    public function reject(User $customer, string $bookingUuid, string $offerUuid): BookingOffer
    {
        [$booking, $offerRow] = $this->resolve($customer, $bookingUuid, $offerUuid);

        return $this->db->transaction(function () use ($booking, $offerRow): BookingOffer {
            $locked = $this->bookings->lockById($booking->id);
            $offer = $this->offers->lockOffer($offerRow->id);

            if (! $locked instanceof Booking || ! $offer instanceof BookingOffer) {
                throw (new ModelNotFoundException)->setModel(BookingOffer::class);
            }

            if (! $locked->status->isSearching()) {
                throw ConflictException::make('booking_already_assigned', context: ['booking_status' => $locked->status->value]);
            }

            if (! $offer->status->isActive()) {
                throw ConflictException::make('offer_not_active', context: ['offer_status' => $offer->status->value]);
            }

            $this->offers->close([$offer->id], OfferStatus::RejectedByCustomer, 'rejected_by_customer', CarbonImmutable::now());
            $offer->refresh();
            $this->notifier->declined($locked, $offer);
            $this->settlement->afterOfferLoss($locked);

            return $this->offers->loadCard($offer);
        });
    }

    /**
     * Another customer's booking is a 404 exactly like a missing one; an offer of another booking too.
     *
     * @return array{0: Booking, 1: BookingOffer}
     */
    private function resolve(User $customer, string $bookingUuid, string $offerUuid): array
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id);
        $offer = $this->offers->findByUuid($offerUuid);

        if (! $booking instanceof Booking) {
            throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        if (! $offer instanceof BookingOffer || $offer->booking_id !== $booking->id) {
            throw (new ModelNotFoundException)->setModel(BookingOffer::class);
        }

        return [$booking, $offer];
    }

    private function providerCanWork(BookingOffer $offer, Booking $booking): bool
    {
        $provider = $this->providers->find($offer->provider_profile_id);

        return $provider instanceof ProviderProfile
            && $this->eligibility->check($provider, $booking->serviceCategory, EligibilityScope::Working)->isEligible();
    }

    private function newPin(): string
    {
        $digits = max(4, (int) config('booking.offers.pin_digits', 4));

        return str_pad((string) random_int(0, (10 ** $digits) - 1), $digits, '0', STR_PAD_LEFT);
    }
}
