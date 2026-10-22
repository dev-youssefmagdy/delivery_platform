<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ArrivalData;
use App\DTOs\Booking\TransitionContext;
use App\DTOs\Booking\TripActionResult;
use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Enums\Payments\PaymentMethodType;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Finance\BookingSettlementService;
use App\Services\Payments\FreeBookingCompletionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The provider's side of a trip after selection (SRS §7.5, §8.3): acknowledge, arrived, start, complete. Each action
 * checks ownership (another provider's booking is a 404), states the facts the guards need (PIN verified, proofs
 * present) read under the booking lock, and moves the booking through `BookingTransitionService`, the only writer of
 * the status. A request for the state the booking is already in is answered as a replay instead of an error, so a retry
 * after a lost response is harmless (the `idempotent` middleware covers repeats of the same key).
 *
 * Totals are never read from the client: completion copies the agreed fare snapshot (plus approved adjustments, none
 * before P4) into `final_fare_minor`; cash trips get `payment_status = cash_due`. The price breakdown is stored and cash
 * trips are settled in the ledger by `BookingSettlementService` in the same transaction (P3.1).
 */
final class TripLifecycleService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly BookingTransitionService $transitions,
        private readonly BookingNotifier $notifier,
        private readonly BookingSettlementService $settlement,
        private readonly FreeBookingCompletionService $freeCompletion,
        private readonly ArrivalEvidenceBuilder $evidence,
        private readonly DatabaseManager $db,
    ) {}

    public function acknowledge(ProviderProfile $provider, string $bookingUuid): TripActionResult
    {
        return $this->run($provider, $bookingUuid, BookingStatus::ProviderHeadingToPickup, static fn (Booking $booking): TransitionContext => new TransitionContext(
            ActorType::Provider,
            $provider->id,
            'provider_acknowledged',
        ));
    }

    public function arrived(ProviderProfile $provider, string $bookingUuid, ArrivalData $arrival): TripActionResult
    {
        return $this->run($provider, $bookingUuid, BookingStatus::ProviderArrived, function (Booking $booking) use ($provider, $arrival): TransitionContext {
            $evidence = $this->evidence->build($booking, $provider, $arrival, CarbonImmutable::now());

            return new TransitionContext(ActorType::Provider, $provider->id, 'provider_arrived', ['arrival_verified' => $evidence['verified']], evidence: $evidence);
        });
    }

    public function start(ProviderProfile $provider, string $bookingUuid): TripActionResult
    {
        return $this->run($provider, $bookingUuid, BookingStatus::InProgress, fn (Booking $booking): TransitionContext => new TransitionContext(
            ActorType::Provider,
            $provider->id,
            'trip_started',
            pinVerified: $booking->pin_verified_at !== null,
            proofsSatisfied: $this->pickupProofPresent($booking),
        ));
    }

    public function complete(ProviderProfile $provider, string $bookingUuid): TripActionResult
    {
        return $this->run($provider, $bookingUuid, $this->completionStatus($this->find($provider, $bookingUuid)), fn (Booking $booking): TransitionContext => new TransitionContext(
            ActorType::Provider,
            $provider->id,
            'trip_completed',
            proofsSatisfied: $this->deliveryProofsPresent($booking),
        ), function (Booking $completed) use ($provider): void {
            // Same transaction as the status change: the price breakdown is stored and a cash trip is settled in the
            // ledger, or the completion does not happen (a retry is safe: both steps are idempotent).
            $completed = $this->settlement->onCompleted($completed);
            // A coupon that covers the whole payable leaves nothing to charge: complete without a gateway payment (P3.3).
            $completed = $this->freeCompletion->completeIfNothingToPay($completed);
            $this->providers->incrementCompletedJobs($provider->id);
            $this->notifier->completed($completed);
        });
    }

    /**
     * The status a finished trip lands in: cash is settled by hand, so it is complete at once and the money state is
     * `cash_due`; every other method waits for its payment (P3.2).
     */
    private function completionStatus(Booking $booking): BookingStatus
    {
        return $booking->payment_method === PaymentMethodType::Cash ? BookingStatus::Completed : BookingStatus::CompletedPendingPayment;
    }

    /**
     * @param callable(Booking): TransitionContext $context
     * @param (callable(Booking): void)|null $afterTransition runs in the same transaction after the status changed
     */
    private function run(ProviderProfile $provider, string $bookingUuid, BookingStatus $target, callable $context, ?callable $afterTransition = null): TripActionResult
    {
        $booking = $this->find($provider, $bookingUuid);

        return $this->db->transaction(function () use ($provider, $booking, $target, $context, $afterTransition): TripActionResult {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking || $locked->provider_id !== $provider->id) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if ($locked->status === $target) {
                return new TripActionResult($locked, true);
            }

            $moved = $this->transitions->transition($locked, $target, $context($locked));

            if ($afterTransition !== null) {
                $afterTransition($moved);
                $moved = $moved->refresh();
            }

            return new TripActionResult($moved);
        });
    }

    private function find(ProviderProfile $provider, string $bookingUuid): Booking
    {
        return $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    /**
     * Pickup evidence when the category requires it: a verified PIN, a photo, a checklist, a weight or a signature.
     */
    private function pickupProofPresent(Booking $booking): bool
    {
        $present = $this->proofs->typesFor($booking->id);

        foreach ([ProofType::PickupPin, ProofType::Photo, ProofType::Checklist, ProofType::Weight, ProofType::Signature] as $type) {
            if (in_array($type->value, $present, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every proof type the category demands at delivery is on the booking (rides demand none).
     */
    private function deliveryProofsPresent(Booking $booking): bool
    {
        $required = array_map('strval', (array) ($booking->policy_snapshot['safety']['delivery_proof_types'] ?? []));

        return array_diff($required, $this->proofs->typesFor($booking->id)) === [];
    }
}
