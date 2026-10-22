<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ArrivalData;
use App\DTOs\Booking\TransitionContext;
use App\DTOs\Booking\TripActionResult;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Catalog\ProofStage;
use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Enums\Payments\PaymentMethodType;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Courier\CustodyService;
use App\Services\Courier\RecipientOtpService;
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
 * Totals are never read from the client: completion sets `final_fare_minor` to the agreed fare snapshot plus the approved
 * adjustments (P3.5) and issues the immutable receipt; cash trips get `payment_status = cash_due`. The price breakdown is stored and cash
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
        private readonly BookingReceiptService $receipts,
        private readonly BookingAdjustmentService $adjustments,
        private readonly CustodyService $custody,
        private readonly RecipientOtpService $recipientOtps,
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
        ), function (Booking $started) use ($provider): void {
            // The parcel is now in the provider's custody (first start only; a retry after a failed attempt does not repeat it).
            if (CustodyService::applies($started) && $started->custody_status === null) {
                $this->custody->move($started, CustodyStatus::PickedUp, TransitionContext::provider($provider, 'custody'));
                $this->recipientOtps->issue($started->refresh());
            }
        });
    }

    public function complete(ProviderProfile $provider, string $bookingUuid): TripActionResult
    {
        return $this->run($provider, $bookingUuid, $this->completionStatus($this->find($provider, $bookingUuid)), fn (Booking $booking): TransitionContext => new TransitionContext(
            ActorType::Provider,
            $provider->id,
            'trip_completed',
            proofsSatisfied: $this->deliveryProofsPresent($booking),
            recipientOtpVerified: $this->recipientOtpVerified($booking),
            returnLeg: $booking->custody_status === CustodyStatus::Returning,
        ), function (Booking $completed, Booking $before) use ($provider): void {
            // The custody chain ends with the hand-over, or with the parcel back at the sender (custody is only tracked for parcels).
            if (CustodyService::applies($completed) && $before->custody_status !== null) {
                $this->custody->move($completed, $before->custody_status === CustodyStatus::Returning ? CustodyStatus::Returned : CustodyStatus::Delivered, TransitionContext::provider($provider, 'custody'));
            }

            // Same transaction as the status change: the price breakdown is stored and a cash trip is settled in the
            // ledger, or the completion does not happen (a retry is safe: both steps are idempotent).
            $completed = $this->settlement->onCompleted($completed);
            // A coupon that covers the whole payable leaves nothing to charge: complete without a gateway payment (P3.3).
            $completed = $this->freeCompletion->completeIfNothingToPay($completed);
            // Requests the other party never answered cannot change the price any more; the receipt is issued once, now (P3.5).
            $this->adjustments->expireOpen($completed, 'booking_completed', CarbonImmutable::now());
            $this->receipts->issue($completed->refresh());
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
     * @param (callable(Booking, Booking): void)|null $afterTransition runs in the same transaction after the status changed (the moved booking, then the locked booking as it was before)
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
                $afterTransition($moved, $locked);
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
        $present = $this->proofs->typesFor($booking->id, ProofStage::Pickup);

        foreach ([ProofType::PickupPin, ProofType::Photo, ProofType::Checklist, ProofType::Weight, ProofType::Signature] as $type) {
            if (in_array($type->value, $present, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every proof type the category demands at delivery is on the booking, taken at the delivery stage (rides demand none).
     * On the way back to the sender the category's list does not apply: the configured return proofs do (a photo).
     */
    private function deliveryProofsPresent(Booking $booking): bool
    {
        if ($booking->custody_status === CustodyStatus::Returning) {
            return array_diff(array_map('strval', (array) config('courier.proofs.return_proof_types', ['photo'])), $this->proofs->typesFor($booking->id, ProofStage::Return)) === [];
        }

        $required = array_map('strval', (array) ($booking->policy_snapshot['safety']['delivery_proof_types'] ?? []));

        return array_diff($required, $this->proofs->typesFor($booking->id, ProofStage::Delivery)) === [];
    }

    /**
     * The recipient code was verified on this booking (a `recipient_otp` proof exists), or the category asks for none.
     */
    private function recipientOtpVerified(Booking $booking): bool
    {
        return ! RecipientOtpService::requiredFor($booking) || $this->proofs->exists($booking->id, ProofType::RecipientOtp);
    }
}
