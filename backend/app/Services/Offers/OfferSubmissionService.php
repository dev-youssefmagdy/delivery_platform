<?php

namespace App\Services\Offers;

use App\DTOs\Booking\OfferSubmissionResult;
use App\DTOs\Booking\SubmitOfferData;
use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\FareHint;
use App\Enums\Booking\OfferKind;
use App\Enums\Booking\OfferStatus;
use App\Enums\Dispatch\DispatchResponse;
use App\Enums\Provider\EligibilityScope;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Exceptions\ProviderNotEligibleException;
use App\Jobs\Booking\ExpireOffer;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Booking\BookingTransitionService;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Scope\OfferScopeService;
use App\Support\Booking\CounterOfferBounds;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A provider answers a dispatched request: accept the customer's fare, counter-offer, revise, withdraw or skip
 * (SRS §7.3, §7.4, §9.3, §10.2 step 4). Everything runs under the booking's row lock, so an offer can never be
 * added to a booking that was just assigned, and the per-provider limits cannot be beaten by two parallel taps.
 *
 * - Guards: the provider was dispatched this booking; the Working eligibility passes with a usable vehicle; the
 *   booking is searching and its window is open; counter-offers are enabled, in the category's bounds and in the
 *   booking's currency; the offer count and the revision count are within the booking's policy snapshot.
 * - An offer's amount is never edited: the first submission creates the offer and version 1, a revision appends the
 *   next version (Plan I3). A repeated request with the same Idempotency-Key returns the stored result.
 * - Hints and the earnings preview are returned to the provider only; nothing here changes a price.
 */
final class OfferSubmissionService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
        private readonly DispatchRecipientRepositoryInterface $recipients,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderEligibilityService $eligibility,
        private readonly BookingTransitionService $transitions,
        private readonly OfferNotifier $notifier,
        private readonly OfferCloser $closer,
        private readonly OfferSettlement $settlement,
        private readonly OfferScopeService $scopes,
        private readonly DatabaseManager $db,
    ) {}

    public function acceptCustomerFare(ProviderProfile $provider, string $bookingUuid, SubmitOfferData $data): OfferSubmissionResult
    {
        return $this->submit($provider, $bookingUuid, OfferKind::AcceptCustomerFare, $data);
    }

    public function counterOffer(ProviderProfile $provider, string $bookingUuid, SubmitOfferData $data): OfferSubmissionResult
    {
        return $this->submit($provider, $bookingUuid, OfferKind::CounterOffer, $data);
    }

    /**
     * @throws ConflictException `offer_not_withdrawable`
     * @throws ForbiddenActionException `offer_not_yours`
     */
    public function withdraw(ProviderProfile $provider, string $offerUuid): BookingOffer
    {
        $found = $this->offers->findByUuid($offerUuid);

        if (! $found instanceof BookingOffer) {
            throw (new ModelNotFoundException)->setModel(BookingOffer::class);
        }

        if ($found->provider_profile_id !== $provider->id) {
            throw ForbiddenActionException::make('offer_not_yours');
        }

        return $this->db->transaction(function () use ($found): BookingOffer {
            $booking = $this->bookings->lockById($found->booking_id);
            $offer = $this->offers->lockOffer($found->id);

            if (! $booking instanceof Booking || ! $offer instanceof BookingOffer) {
                throw (new ModelNotFoundException)->setModel(BookingOffer::class);
            }

            if (! $offer->status->isActive()) {
                throw ConflictException::make('offer_not_withdrawable', context: ['offer_status' => $offer->status->value]);
            }

            $this->closer->close($booking, collect([$offer]), OfferStatus::WithdrawnByProvider, 'withdrawn_by_provider');
            $this->settlement->afterOfferLoss($booking);

            return $this->offers->loadCard($offer->refresh())->load('booking');
        });
    }

    /**
     * "Not for me": records the response, no penalty (SRS §7.3). Needs the offer withdrawn first.
     */
    public function skip(ProviderProfile $provider, string $bookingUuid): void
    {
        $booking = $this->bookings->findByUuid($bookingUuid);

        if (! $booking instanceof Booking || $this->recipients->findRecipient($booking->id, $provider->id) === null) {
            throw ForbiddenActionException::make('offer_not_dispatched');
        }

        $this->db->transaction(function () use ($booking, $provider): void {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                return;
            }

            if ($this->offers->lockActiveFor($locked->id, $provider->id) instanceof BookingOffer) {
                throw ConflictException::make('offer_withdraw_first');
            }

            $this->recipients->respond($locked->id, $provider->id, DispatchResponse::Declined);
        });
    }

    private function submit(ProviderProfile $provider, string $bookingUuid, OfferKind $kind, SubmitOfferData $data): OfferSubmissionResult
    {
        $booking = $this->bookings->findByUuid($bookingUuid);

        if (! $booking instanceof Booking) {
            throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        $replay = $this->replayOf($provider, $data);

        if ($replay instanceof OfferSubmissionResult) {
            return $replay;
        }

        return $this->db->transaction(function () use ($provider, $booking, $kind, $data): OfferSubmissionResult {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            $recipient = $this->recipients->findRecipient($locked->id, $provider->id);

            if ($recipient === null) {
                throw ForbiddenActionException::make('offer_not_dispatched');
            }

            $now = CarbonImmutable::now();
            $this->assertOpen($locked, $now);

            if ($kind === OfferKind::CounterOffer && ! CounterOfferBounds::negotiationEnabled($locked)) {
                throw BusinessRuleViolation::make('counter_offers_disabled');
            }

            $fresh = $this->providers->find($provider->id);
            $vehicleId = $this->assertCanWork($fresh instanceof ProviderProfile ? $fresh : $provider, $locked);
            $amount = $kind === OfferKind::AcceptCustomerFare ? $locked->proposed_fare_minor : $this->counterAmount($locked, $data);

            $existing = $this->offers->lockActiveFor($locked->id, $provider->id);
            $policy = (array) ($locked->policy_snapshot['offers'] ?? []);
            $note = $this->cleanNote($data->note);
            // Moving / freight: the vehicle must carry the load, a counter-offer must carry a valid scope and an accepted customer
            // fare carries the scope the customer requested (SRS §7.4, §10.1).
            $offeringVehicle = $this->vehicles->find($vehicleId);

            if (! $offeringVehicle instanceof Vehicle) {
                throw BusinessRuleViolation::make('offer_vehicle_missing');
            }

            $this->scopes->assertCapacity($locked, $offeringVehicle);
            $scope = $this->scopes->forOffer($locked, $offeringVehicle, $kind === OfferKind::CounterOffer, $data->scope);

            if ($existing instanceof BookingOffer) {
                $existing->loadMissing('currentVersion');
                $current = $existing->currentVersion;

                if ($current !== null && $current->amount_minor === $amount && $current->note === $note // Loose comparison: the JSON column does not keep key order.
                    && ($current->scope ?? []) === ($scope ?? [])) {
                    return $this->result($locked, $existing, $now, replayed: true, revision: false);
                }

                if ($existing->revision_count >= (int) ($policy['max_revisions'] ?? 0)) {
                    throw BusinessRuleViolation::make('offer_revisions_exceeded');
                }
            } elseif ($this->offers->countForProvider($locked->id, $provider->id) >= max(1, (int) ($policy['max_per_provider'] ?? 1))) {
                throw BusinessRuleViolation::make('offer_limit_reached');
            }

            $expiresAt = $this->expiryFor($locked, $now);
            $eta = $data->etaMinutes === null ? null : $data->etaMinutes * 60;
            $offer = $existing ?? $this->offers->createOffer([
                'booking_id' => $locked->id,
                'provider_profile_id' => $provider->id,
                'vehicle_id' => $vehicleId,
                'kind' => $kind,
                'status' => $kind->activeStatus(),
                'expires_at' => $expiresAt,
                'eta_seconds' => $eta,
                'distance_m' => $recipient->distance_m,
                'idempotency_key' => $data->idempotencyKey,
                'active_marker' => 1,
            ]);

            $money = $locked->proposed_fare?->currency;
            $version = $this->offers->addVersion($offer, Money::of($amount, $money ?? throw BusinessRuleViolation::make('offer_currency_mismatch')), $note, $scope, $data->idempotencyKey);

            $this->offers->applyState($offer, [
                'kind' => $kind,
                'status' => $kind->activeStatus(),
                'current_version_id' => $version->id,
                'expires_at' => $expiresAt,
                'eta_seconds' => $eta ?? $offer->eta_seconds,
                'revision_count' => $existing instanceof BookingOffer ? $existing->revision_count + 1 : 0,
            ]);

            $this->recipients->respond($locked->id, $provider->id, DispatchResponse::Offered);

            if ($locked->status === BookingStatus::SearchingOffers) {
                $locked = $this->transitions->transition($locked, BookingStatus::OffersReceived, TransitionContext::provider($provider, 'first_offer'));
            }

            $offer->refresh();

            if ($existing instanceof BookingOffer) {
                $this->notifier->revised($locked, $offer);
            } else {
                $this->notifier->received($locked, $offer);
            }

            ExpireOffer::dispatch($offer->id)->delay($expiresAt)->afterCommit();

            return $this->result($locked, $offer, $now, replayed: false, revision: $existing instanceof BookingOffer);
        });
    }

    /**
     * A repeated request (same provider, same Idempotency-Key) returns what the first one produced. The key proves
     * the intent, so a different amount under the same key is refused instead of silently applied.
     */
    private function replayOf(ProviderProfile $provider, SubmitOfferData $data): ?OfferSubmissionResult
    {
        if ($data->idempotencyKey === null) {
            return null;
        }

        $version = $this->offers->findVersionByKey($provider->id, $data->idempotencyKey);

        if ($version === null) {
            return null;
        }

        $offer = $this->offers->find($version->offer_id);

        if (! $offer instanceof BookingOffer) {
            return null;
        }

        if ($data->amountMinor !== null && $data->amountMinor !== $version->amount_minor) {
            throw ConflictException::make('offer_idempotency_conflict');
        }

        $booking = $this->bookings->find($offer->booking_id);

        if (! $booking instanceof Booking) {
            return null;
        }

        return $this->result($booking, $this->offers->loadCard($offer), CarbonImmutable::now(), replayed: true, revision: $version->version > 1);
    }

    private function assertOpen(Booking $booking, CarbonImmutable $now): void
    {
        if (! $booking->status->isSearching()) {
            throw ConflictException::make(
                $booking->status->isAssigned() ? 'booking_already_assigned' : 'booking_not_open_for_offers',
                context: ['booking_status' => $booking->status->value],
            );
        }

        if ($booking->search_expires_at === null || $booking->search_expires_at->lessThanOrEqualTo($now)) {
            throw ConflictException::make('booking_search_expired', context: ['booking_status' => $booking->status->value]);
        }
    }

    /**
     * @return int the vehicle the offer is made with
     */
    private function assertCanWork(ProviderProfile $provider, Booking $booking): int
    {
        $result = $this->eligibility->check($provider, $booking->serviceCategory, EligibilityScope::Working);

        if (! $result->isEligible()) {
            throw ProviderNotEligibleException::because($result);
        }

        if ($provider->current_vehicle_id === null || ! in_array($provider->current_vehicle_id, $result->usableVehicleIds, true)) {
            throw BusinessRuleViolation::make('offer_vehicle_missing');
        }

        return $provider->current_vehicle_id;
    }

    private function counterAmount(Booking $booking, SubmitOfferData $data): int
    {
        $amount = $data->amountMinor;

        if ($amount === null || $amount <= 0) {
            throw BusinessRuleViolation::make('offer_amount_invalid', 'amount_minor');
        }

        if ($data->currency !== null && strtoupper($data->currency) !== $booking->currency) {
            throw BusinessRuleViolation::make('offer_currency_mismatch', 'currency');
        }

        if ($amount === $booking->proposed_fare_minor) {
            throw BusinessRuleViolation::make('offer_amount_same_as_fare', 'amount_minor');
        }

        $bounds = CounterOfferBounds::for($booking);

        if (($bounds['min'] !== null && $amount < $bounds['min']) || ($bounds['max'] !== null && $amount > $bounds['max'])) {
            throw BusinessRuleViolation::make('offer_amount_out_of_bounds', 'amount_minor', ['min' => $bounds['min'] ?? 1, 'max' => $bounds['max'] ?? 'unbounded']);
        }

        return $amount;
    }

    private function cleanNote(?string $note): ?string
    {
        $note = $note === null ? null : trim($note);

        return $note === null || $note === '' ? null : mb_substr($note, 0, 500);
    }

    /**
     * The offer lives for the category's offer TTL but never longer than the search window (SRS §6.5: all offers expire
     * when the request expires).
     */
    private function expiryFor(Booking $booking, CarbonImmutable $now): CarbonImmutable
    {
        $window = CarbonImmutable::instance($booking->search_expires_at ?? $now);
        $ttl = (int) ($booking->policy_snapshot['dispatch']['offer_ttl_seconds'] ?? 0);

        if ($ttl <= 0) {
            return $window;
        }

        $byTtl = $now->addSeconds($ttl);

        return $byTtl->lessThan($window) ? $byTtl : $window;
    }

    private function result(Booking $booking, BookingOffer $offer, CarbonImmutable $now, bool $replayed, bool $revision): OfferSubmissionResult
    {
        $offer->loadMissing(['currentVersion', 'booking']);
        $amount = (int) ($offer->currentVersion->amount_minor ?? 0);

        return new OfferSubmissionResult(
            $this->offers->loadCard($offer),
            $replayed,
            $revision,
            array_map(static fn (FareHint $hint): string => $hint->value, $this->hints($booking, $offer, $amount, $now)),
            // Commission is configured by the money core (P3.1); until then the preview says it is not available.
            ['available' => false, 'gross_minor' => $amount, 'currency' => $booking->currency, 'commission_minor' => null, 'net_minor' => null],
        );
    }

    /**
     * Informational only (SRS §9.3): against the reference estimate stored on the booking and the other open offers.
     *
     * @return list<FareHint>
     */
    private function hints(Booking $booking, BookingOffer $offer, int $amount, CarbonImmutable $now): array
    {
        $hints = [];
        $estimate = (array) ($booking->reference_estimate ?? []);

        if (($estimate['available'] ?? false) === true) {
            if ($amount < (int) $estimate['low_minor']) {
                $hints[] = FareHint::BelowReference;
            } elseif ($amount > (int) $estimate['high_minor']) {
                $hints[] = FareHint::AboveReference;
            }
        }

        $others = $this->offers->openAmountsExcept($booking->id, $offer->provider_profile_id, $now);

        if ($others !== []) {
            if ($amount < min($others)) {
                $hints[] = FareHint::BelowRecentOffers;
            } elseif ($amount > max($others)) {
                $hints[] = FareHint::AboveRecentOffers;
            }
        }

        return $hints;
    }
}
