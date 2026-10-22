<?php

namespace App\Services\Booking;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Provider\EligibilityScope;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Booking\BookingStatusTransitioned;
use App\Events\Realtime\BookingStatusChanged;
use App\Events\Realtime\TripStatusChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Booking\ShareLinkRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Coupon\CouponReservationService;
use App\Services\Dispatch\EventSequencer;
use App\Services\Finance\CancellationFeePostingService;
use App\Services\Provider\ProviderEligibilityService;
use App\Support\Booking\BookingStatusGate;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The only writer of `bookings.status` (ADR-16, Plan §7.2). `BookingStatus::allowedTransitions()` owns the graph;
 * this service takes the row lock, checks the edge and the guard of the target status (SRS §8.3), applies the column
 * changes that belong to the transition, appends the `booking_status_events` row with the booking's own sequence
 * number, and broadcasts after commit. A repeated or racing request that no longer fits the graph is a 409, never a
 * second write.
 *
 * Guards state what must be true; callers state the facts through `TransitionContext` (offer, PIN, proofs), so the
 * flows of P2.4 (selection), P2.5 (trip, cancellation) and P3.x (fees, disputes) plug in without a second status writer.
 */
final class BookingTransitionService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingStatusEventRepositoryInterface $events,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly ProviderEligibilityService $eligibility,
        private readonly EventSequencer $sequencer,
        private readonly ShareLinkRepositoryInterface $shareLinks,
        private readonly CouponReservationService $coupons,
        private readonly CancellationFeePostingService $feePostings,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Writes the creation entry (null -> draft) of a booking that was just inserted. Call inside the creating transaction.
     */
    public function recordCreation(Booking $booking, TransitionContext $context): BookingStatusEvent
    {
        return $this->events->append([
            'booking_id' => $booking->id,
            'from_status' => null,
            'to_status' => $booking->status,
            'actor_type' => $context->actorType->value,
            'actor_id' => $context->actorId,
            'reason' => $context->reason,
            'metadata' => $context->metadata === [] ? null : $context->metadata,
            'sequence' => $this->sequencer->next($booking->uuid),
        ]);
    }

    /**
     * Records something that happened to the booking without changing its status (for example "request changed"):
     * an event with from = to. Call inside a transaction that holds the booking lock.
     *
     * @param array<string, mixed> $metadata
     */
    public function annotate(Booking $booking, string $reason, TransitionContext $context, array $metadata = []): BookingStatusEvent
    {
        return $this->events->append([
            'booking_id' => $booking->id,
            'from_status' => $booking->status,
            'to_status' => $booking->status,
            'actor_type' => $context->actorType->value,
            'actor_id' => $context->actorId,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
            'sequence' => $this->sequencer->next($booking->uuid),
        ]);
    }

    /**
     * @throws ConflictException `booking_transition_invalid`, `booking_already_assigned`
     * @throws BusinessRuleViolation a guard rejected the transition
     * @throws ForbiddenActionException the actor may not perform it
     */
    public function transition(Booking $booking, BookingStatus $to, TransitionContext $context): Booking
    {
        return $this->db->transaction(function () use ($booking, $to, $context): Booking {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            $from = $locked->status;
            $previousProviderId = $locked->provider_id;

            if (! $from->canTransitionTo($to)) {
                throw ConflictException::make('booking_transition_invalid', context: ['from' => $from->value, 'to' => $to->value]);
            }

            $this->guard($locked, $from, $to, $context);

            BookingStatusGate::run(fn (): Booking => $this->bookings->forceUpdate(
                $locked,
                ['status' => $to, 'lock_version' => $locked->lock_version + 1] + $this->changes($locked, $from, $to, $context),
            ));

            $sequence = $this->sequencer->next($locked->uuid);
            $this->events->append([
                'booking_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor_type' => $context->actorType->value,
                'actor_id' => $context->actorId,
                'reason' => $context->reason,
                'metadata' => $context->metadata === [] ? null : $context->metadata,
                'sequence' => $sequence,
            ]);

            $locked->refresh();

            // P3.5: the fee decision of a cancellation (or of a provider cancel that sends the booking back to search) is
            // posted to the ledger in this very transaction; a failed posting rolls the whole transition back.
            if ($context->feeDecision !== null && ($to->isCancellation() || ($to === BookingStatus::SearchingOffers && $from->isAssigned()))) {
                $this->feePostings->post($locked, $context->feeDecision, $previousProviderId, $sequence);
            }

            // ADR-17: a booking that ends without a trip gives its coupon use and held budget back (P3.5: unless a fee keeps it).
            if ($to === BookingStatus::ExpiredNoProvider) {
                $this->coupons->release($locked, 'booking_expired');
            } elseif ($to->isCancellation()) {
                // P3.5: a late cancellation fee keeps the coupon use when the coupon's policy says so.
                $this->coupons->settleCancelled($locked, $this->customerFeeMinor($context), 'booking_cancelled');
            }

            if (in_array($to, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment, BookingStatus::ExpiredNoProvider], true) || $to->isCancellation()) {
                $this->shareLinks->revokeForBooking($locked->id, $context->now());
            }

            $payload = ['status' => $to->value, 'previous_status' => $from->value, 'request_version' => $locked->request_version];
            BookingStatusChanged::dispatch($locked->uuid, $sequence, $payload);
            BookingStatusTransitioned::dispatch($locked->id, $from, $to, $sequence);
            $this->announceToParties($locked, $sequence, $payload + ['actor' => $context->actorType->value], $previousProviderId);

            // Back from `offers_received` the dispatch chain is still running and the window is unchanged.
            if ($to === BookingStatus::SearchingOffers && $from !== BookingStatus::OffersReceived) {
                BookingEnteredSearch::dispatch($locked->id, restart: $from->isAssigned(), keepProviderIds: $from->isAssigned() && $previousProviderId !== null ? [$previousProviderId] : []);
            }

            return $locked;
        });
    }

    /**
     * The booking channel is shared with every dispatched provider while the search runs, so the parties also get the
     * change on their own channels: the customer, the assigned provider, and a provider who just lost the booking
     * (cancelled back to searching) so its app drops the job (ADR-98).
     *
     * @param array<string, mixed> $payload
     */
    private function announceToParties(Booking $booking, int $sequence, array $payload, ?int $previousProviderId): void
    {
        $booking->loadMissing('customer');
        TripStatusChanged::toCustomer($booking->customer->uuid, $booking->uuid, $sequence, $payload);

        foreach (array_unique(array_filter([$booking->provider_id, $previousProviderId])) as $providerId) {
            $provider = $this->providers->find($providerId);

            if ($provider instanceof ProviderProfile) {
                TripStatusChanged::toProvider($provider->uuid, $booking->uuid, $sequence, $payload);
            }
        }
    }

    private function guard(Booking $booking, BookingStatus $from, BookingStatus $to, TransitionContext $context): void
    {
        match ($to) {
            BookingStatus::SearchingOffers => $this->guardToSearching($booking, $from, $context),
            BookingStatus::ExpiredNoProvider => $this->guardToExpired($booking, $context),
            BookingStatus::ProviderSelected => $this->guardToProviderSelected($booking, $context),
            BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived => $this->requireAssignedProvider($booking, $context),
            BookingStatus::InProgress => $this->guardToInProgress($booking, $context),
            BookingStatus::CompletedPendingPayment, BookingStatus::Completed => $this->guardToCompleted($booking, $from, $context),
            BookingStatus::CancelledByCustomer => $this->guardToCancelledByCustomer($booking, $context),
            BookingStatus::CancelledByProvider => $this->guardToCancelledByProvider($booking, $context),
            BookingStatus::CancelledByOperations => $this->guardToCancelledByOperations($context),
            BookingStatus::DeliveryAttemptFailed => $this->requireAssignedProvider($booking, $context),
            BookingStatus::Draft, BookingStatus::OffersReceived, BookingStatus::Disputed => null,
        };
    }

    /**
     * Back to searching after a provider cancelled needs the category policy to allow automatic re-dispatch; a draft
     * needs its search window to be defined.
     */
    private function guardToSearching(Booking $booking, BookingStatus $from, TransitionContext $context): void
    {
        if ($from->isAssigned()) {
            $this->requireAssignedProvider($booking, $context);
        }

        if ($this->searchTtlSeconds($booking) <= 0) {
            throw BusinessRuleViolation::make('booking_search_policy_missing');
        }

        if ($from->isAssigned() && ! (bool) ($booking->policy_snapshot['dispatch']['auto_redispatch'] ?? false)) {
            throw BusinessRuleViolation::make('booking_redispatch_not_allowed');
        }
    }

    /**
     * The search window must really be over: the sweep and the delayed job are backstops, but neither may end a
     * search early. Operations can end it explicitly.
     */
    private function guardToExpired(Booking $booking, TransitionContext $context): void
    {
        if ($context->actorType === ActorType::Admin) {
            return;
        }

        if ($booking->search_expires_at === null || $booking->search_expires_at->greaterThan($context->now())) {
            throw BusinessRuleViolation::make('booking_search_not_expired');
        }
    }

    /**
     * The booking has no provider yet, the chosen offer was verified by the caller (it passes the offer id) and the
     * provider is still eligible to work (Working scope: approved, documents, vehicle, location fresh, no other job).
     */
    private function guardToProviderSelected(Booking $booking, TransitionContext $context): void
    {
        if ($booking->provider_id !== null || $booking->selected_offer_id !== null) {
            throw ConflictException::make('booking_already_assigned');
        }

        if ($context->providerId === null || $context->offerId === null) {
            throw BusinessRuleViolation::make('booking_selection_incomplete');
        }

        $provider = $this->providers->find($context->providerId);

        if (! $provider instanceof ProviderProfile) {
            throw BusinessRuleViolation::make('booking_provider_not_eligible');
        }

        if (! $this->eligibility->check($provider, $booking->serviceCategory, EligibilityScope::Working)->isEligible()) {
            throw BusinessRuleViolation::make('booking_provider_not_eligible');
        }
    }

    /**
     * Starting the trip from arrival needs the PIN (when the category requires it) and the pickup proof; a retry
     * after a failed delivery attempt does not repeat them.
     */
    private function guardToInProgress(Booking $booking, TransitionContext $context): void
    {
        // After a failed delivery attempt the customer may ask for another one; every other start is the provider's.
        if ($booking->status === BookingStatus::DeliveryAttemptFailed && $context->actorType === ActorType::Customer && $context->actorId === $booking->customer_id) {
            return;
        }

        $this->requireAssignedProvider($booking, $context);

        if ($booking->status !== BookingStatus::ProviderArrived) {
            return;
        }

        if ((bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false) && ! $context->pinVerified) {
            throw BusinessRuleViolation::make('booking_pin_required');
        }

        if ((bool) ($booking->policy_snapshot['safety']['pickup_proof_required'] ?? false) && ! $context->proofsSatisfied) {
            throw BusinessRuleViolation::make('booking_proof_required');
        }
    }

    private function guardToCompleted(Booking $booking, BookingStatus $from, TransitionContext $context): void
    {
        if ($from === BookingStatus::InProgress) {
            $this->requireAssignedProvider($booking, $context);

            if ($booking->selected_offer_id === null || $booking->agreed_fare_minor === null) {
                throw BusinessRuleViolation::make('booking_fare_not_agreed');
            }

            $proofs = (array) ($booking->policy_snapshot['safety']['delivery_proof_types'] ?? []);

            if ($proofs !== [] && ! $context->proofsSatisfied) {
                throw BusinessRuleViolation::make('booking_proof_required');
            }

            // The recipient code is part of the category's contract (SRS §5.1-C); the way back to the sender cannot have one,
            // and operations may finish a hand-over whose recipient lost the code (audited, with a reason).
            if ((bool) ($booking->policy_snapshot['safety']['recipient_otp_required'] ?? false) && ! $context->returnLeg && ! $context->recipientOtpVerified && $context->actorType !== ActorType::Admin) {
                throw BusinessRuleViolation::make('booking_recipient_otp_required');
            }
        }
    }

    private function guardToCancelledByCustomer(Booking $booking, TransitionContext $context): void
    {
        if ($context->actorType !== ActorType::Customer || $context->actorId !== $booking->customer_id) {
            throw ForbiddenActionException::make('booking_not_yours');
        }
    }

    private function guardToCancelledByProvider(Booking $booking, TransitionContext $context): void
    {
        $this->requireAssignedProvider($booking, $context);

        if ($context->reason === null || trim($context->reason) === '') {
            throw BusinessRuleViolation::make('booking_cancellation_reason_required', 'reason');
        }
    }

    private function guardToCancelledByOperations(TransitionContext $context): void
    {
        if ($context->actorType !== ActorType::Admin && $context->actorType !== ActorType::System) {
            throw ForbiddenActionException::make('booking_operations_only');
        }

        if ($context->reason === null || trim($context->reason) === '') {
            throw BusinessRuleViolation::make('booking_cancellation_reason_required', 'reason');
        }
    }

    /**
     * The acting provider must be the booking's provider (operations may act for them).
     */
    private function requireAssignedProvider(Booking $booking, TransitionContext $context): void
    {
        if ($booking->provider_id === null) {
            throw BusinessRuleViolation::make('booking_provider_missing');
        }

        $owner = $context->actorType === ActorType::Provider && $context->actorId === $booking->provider_id;

        if (! $owner && $context->actorType !== ActorType::Admin) {
            throw ForbiddenActionException::make('booking_not_assigned_to_you');
        }
    }

    /**
     * Column changes that belong to the transition itself.
     *
     * @return array<string, mixed>
     */
    private function changes(Booking $booking, BookingStatus $from, BookingStatus $to, TransitionContext $context): array
    {
        $now = $context->now();

        return match (true) {
            $to === BookingStatus::SearchingOffers && $from === BookingStatus::OffersReceived => [],
            // Back to searching after the provider cancelled: nothing of the previous assignment survives on the
            // booking (the history stays in the events and the closed offer), the PIN is regenerated at the next selection.
            $to === BookingStatus::SearchingOffers => [
                'search_expires_at' => $now->addSeconds($this->searchTtlSeconds($booking)),
                'provider_id' => null,
                'selected_offer_id' => null,
                'agreed_fare_minor' => null,
                'agreed_offer_version_id' => null,
                'agreed_scope' => null,
                // Adjustments belonged to the previous agreement (P3.5): the service closes them and the total starts again.
                'adjustments_total_minor' => 0,
                // The commission is bound at selection (P3.1): a new selection binds it again.
                'commission_snapshot' => null,
                'commission_rule_id' => null,
                'commission_rule_version_id' => null,
                'provider_selected_at' => null,
                'pin_hash' => null,
                'pin_encrypted' => null,
                'pin_attempts' => 0,
                'pin_verified_at' => null,
                'pin_locked_at' => null,
                'support_flagged_at' => null,
                'support_flag_reason' => null,
                'heading_at' => null,
                'arrived_at' => null,
                'arrival_evidence' => null,
                'free_wait_ends_at' => null,
            ],
            $to === BookingStatus::ProviderSelected => ['provider_id' => $context->providerId, 'selected_offer_id' => $context->offerId],
            $to === BookingStatus::ProviderHeadingToPickup => ['heading_at' => $now],
            $to === BookingStatus::ProviderArrived => [
                'arrived_at' => $now,
                'arrival_evidence' => $context->evidence === [] ? null : $context->evidence,
                'free_wait_ends_at' => $now->addSeconds((int) ($booking->policy_snapshot['cancellation']['free_wait_seconds'] ?? 0)),
            ],
            $to === BookingStatus::InProgress && $from === BookingStatus::ProviderArrived => ['started_at' => $now],
            $to->isCancellation() => [
                'cancelled_at' => $now,
                'cancelled_by_type' => $context->actorType->value,
                'cancelled_by_id' => $context->actorId,
                'cancellation_reason' => $context->reason,
                'cancellation_reason_code' => $context->reasonCode,
                'cancellation_fee_minor' => $context->feeDecision?->feeMinor,
                'cancellation_decision' => $context->feeDecision?->toArray(),
            ],
            $from === BookingStatus::InProgress && ($to === BookingStatus::Completed || $to === BookingStatus::CompletedPendingPayment) => [
                'completed_at' => $now,
                // Server-computed total: the agreed fare plus the approved booking adjustments (P3.5), never a client figure.
                'final_fare_minor' => max(0, (int) $booking->agreed_fare_minor + $booking->adjustments_total_minor),
                'payment_status' => $booking->payment_method === PaymentMethodType::Cash ? PaymentStatus::CashDue : PaymentStatus::Pending,
            ],
            $to === BookingStatus::Completed && $booking->completed_at === null => ['completed_at' => $now],
            $to === BookingStatus::CompletedPendingPayment && $booking->completed_at === null => ['completed_at' => $now],
            default => [],
        };
    }

    /**
     * What the customer owes for this cancellation (zero when the provider is the payer or nothing is due).
     */
    private function customerFeeMinor(TransitionContext $context): int
    {
        $decision = $context->feeDecision;

        return $decision !== null && $decision->payer() !== ActorType::Provider ? $decision->feeMinor : 0;
    }

    private function searchTtlSeconds(Booking $booking): int
    {
        return (int) ($booking->policy_snapshot['dispatch']['search_ttl_seconds'] ?? 0);
    }
}
