<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ArrivalData;
use App\DTOs\Booking\CancelBookingData;
use App\DTOs\Booking\CancellationDecision;
use App\DTOs\Booking\CancellationResult;
use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Booking\OfferInvalidationReason;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Cancellation v1 (SRS §8.4, Plan P2.5). Validates actor, stage and reason against the policy snapshot of the booking,
 * decides the fee with `CancellationFeeCalculator` (the preview and the commit run the same code) and moves the booking
 * through `BookingTransitionService`, the only writer of the status.
 *
 * - Customer: allowed from `draft` to `provider_arrived`. `preview` writes nothing and returns the decision. A commit
 *   that owes a fee needs the customer to acknowledge exactly that amount (409 `cancellation_fee_changed` otherwise,
 *   with the fresh decision), because the decision is recomputed under the booking lock.
 * - Provider: allowed from `provider_selected` to `provider_arrived` with a reason from the provider list; a no-show
 *   needs the arrival and the free waiting time to be over. With automatic re-dispatch (snapshot of the setting) the
 *   booking returns to `searching_offers`: assignment, agreed fare, PIN, arrival data and the winner slot are cleared,
 *   the history stays in the events and the closed offer, and the provider is not asked again for this booking.
 *   Otherwise the booking ends as `cancelled_by_provider`.
 * The fee is computed and snapshotted on the booking, and posted to the ledger by BookingTransitionService in the same
 * transaction (P3.5, CancellationFeePostingService). Two no-show flows ride on the same endpoints: the provider's
 * `customer_no_show` (the customer is charged the policy's no-show fee on GPS + time evidence) and the customer's
 * `driver_no_show` (free for the customer, a penalty for the provider).
 * Appeals: a disputed fee will open a support ticket from the booking (P3.7); `meta.appeal` of the decision is the hook.
 */
final class CancellationService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
        private readonly BookingOfferRepositoryInterface $offerBridge,
        private readonly BookingTransitionService $transitions,
        private readonly CancellationFeeCalculator $calculator,
        private readonly BookingNotifier $notifier,
        private readonly ArrivalEvidenceBuilder $evidence,
        private readonly BookingAdjustmentService $adjustments,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `booking_not_cancellable`, `cancellation_reason_not_allowed`, `cancellation_note_required`
     * @throws ConflictException `cancellation_fee_changed`
     */
    public function cancelByCustomer(User $customer, string $bookingUuid, CancelBookingData $data): CancellationResult
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if ($data->preview) {
            $this->assertReason($booking, ActorType::Customer, $data);

            return new CancellationResult($booking, $this->calculator->decide($booking, ActorType::Customer, CarbonImmutable::now(), $data->reason), false);
        }

        return $this->db->transaction(function () use ($customer, $booking, $data): CancellationResult {
            $locked = $this->lock($booking);

            if ($locked->status === BookingStatus::CancelledByCustomer) {
                return new CancellationResult($locked, $this->storedDecision($locked, ActorType::Customer), true, replayed: true);
            }

            $this->assertCancellable($locked, BookingStatus::CancelledByCustomer);
            $this->assertReason($locked, ActorType::Customer, $data);

            $now = CarbonImmutable::now();
            $decision = $this->calculator->decide($locked, ActorType::Customer, $now, $data->reason);

            if ($decision->feeMinor > 0 && $data->acknowledgedFeeMinor !== $decision->feeMinor) {
                throw ConflictException::make('cancellation_fee_changed', 'accepted_fee_minor', ['cancellation' => $decision->toArray()]);
            }

            $this->offerBridge->invalidateActive($locked, OfferInvalidationReason::BookingCancelled);

            $cancelled = $this->transitions->transition($locked, BookingStatus::CancelledByCustomer, new TransitionContext(
                actorType: ActorType::Customer,
                actorId: $customer->id,
                reason: $this->reasonText($data),
                metadata: ['cancellation' => $decision->toArray()] + ($data->idempotencyKey === null ? [] : ['idempotency_key' => $data->idempotencyKey]),
                at: $now,
                reasonCode: $data->reason->value,
                feeDecision: $decision,
            ));

            $this->notifier->cancelled($cancelled, ActorType::Customer->value, $decision->feeMinor);

            return new CancellationResult($cancelled, $decision, true);
        });
    }

    /**
     * @throws BusinessRuleViolation `booking_not_cancellable`, `cancellation_reason_not_allowed`, `cancellation_note_required`, `no_show_too_early`
     */
    public function cancelByProvider(ProviderProfile $provider, string $bookingUuid, CancelBookingData $data): CancellationResult
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if ($data->preview) {
            $this->assertReason($booking, ActorType::Provider, $data);
            $now = CarbonImmutable::now();
            $this->assertNoShowAllowed($booking, $data, $now);

            return new CancellationResult($booking, $this->calculator->decide($booking, ActorType::Provider, $now, $data->reason, $this->noShowEvidence($booking, $provider, $data, $now)), false);
        }

        return $this->db->transaction(function () use ($provider, $booking, $data): CancellationResult {
            $locked = $this->lock($booking);

            if ($locked->status === BookingStatus::CancelledByProvider && $locked->provider_id === $provider->id) {
                return new CancellationResult($locked, $this->storedDecision($locked, ActorType::Provider), true, replayed: true);
            }

            if ($locked->provider_id !== $provider->id) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            $this->assertCancellable($locked, BookingStatus::CancelledByProvider);
            $this->assertReason($locked, ActorType::Provider, $data);

            $now = CarbonImmutable::now();
            $this->assertNoShowAllowed($locked, $data, $now);
            $decision = $this->calculator->decide($locked, ActorType::Provider, $now, $data->reason, $this->noShowEvidence($locked, $provider, $data, $now));
            $redispatch = (bool) ($locked->policy_snapshot['dispatch']['auto_redispatch'] ?? false);

            $context = new TransitionContext(
                actorType: ActorType::Provider,
                actorId: $provider->id,
                reason: $this->reasonText($data),
                metadata: ['cancellation' => $decision->toArray(), 'redispatch' => $redispatch] + ($data->idempotencyKey === null ? [] : ['idempotency_key' => $data->idempotencyKey]),
                at: $now,
                reasonCode: $data->reason->value,
                feeDecision: $decision,
            );

            if ($redispatch) {
                $back = $this->transitions->transition($locked, BookingStatus::SearchingOffers, $context);
                $this->adjustments->closeAll($back, 'provider_changed', $now);
                $this->offers->releaseWinner($back->id, $now);
                $this->notifier->providerCancelledRedispatch($back);

                return new CancellationResult($back, $decision, true, redispatched: true);
            }

            $cancelled = $this->transitions->transition($locked, BookingStatus::CancelledByProvider, $context);
            $this->notifier->cancelled($cancelled, ActorType::Provider->value, $decision->feeMinor);

            return new CancellationResult($cancelled, $decision, true);
        });
    }

    private function lock(Booking $booking): Booking
    {
        $locked = $this->bookings->lockById($booking->id);

        return $locked instanceof Booking ? $locked : throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    private function assertCancellable(Booking $booking, BookingStatus $target): void
    {
        $allowed = in_array($booking->status, [
            BookingStatus::Draft, BookingStatus::SearchingOffers, BookingStatus::OffersReceived,
            BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived,
        ], true);

        if (! $allowed || ! $booking->status->canTransitionTo($target)) {
            throw ConflictException::make('booking_not_cancellable', context: ['booking_status' => $booking->status->value]);
        }
    }

    private function assertReason(Booking $booking, ActorType $actor, CancelBookingData $data): void
    {
        $policy = (array) ($booking->policy_snapshot['cancellation'] ?? CancellationPolicyResolver::defaults());
        $key = $actor === ActorType::Customer ? 'customer_reasons' : 'provider_reasons';
        $allowed = (array) ($policy[$key] ?? CancellationReason::valuesForActor($actor));

        // Reporting a driver who never came cannot be switched off by a policy: it protects the customer.
        $alwaysAllowed = $actor === ActorType::Customer && $data->reason === CancellationReason::DriverNoShow;

        if (! in_array($data->reason->value, CancellationReason::valuesForActor($actor), true) || (! $alwaysAllowed && ! in_array($data->reason->value, $allowed, true))) {
            throw BusinessRuleViolation::make('cancellation_reason_not_allowed', 'reason_code');
        }

        if ($data->reason === CancellationReason::Other && trim((string) $data->note) === '') {
            throw BusinessRuleViolation::make('cancellation_note_required', 'note');
        }
    }

    private function assertNoShowAllowed(Booking $booking, CancelBookingData $data, CarbonImmutable $now): void
    {
        if ($data->reason !== CancellationReason::CustomerNoShow) {
            return;
        }

        if ($booking->status !== BookingStatus::ProviderArrived || ($booking->free_wait_ends_at !== null && $booking->free_wait_ends_at->greaterThan($now))) {
            throw BusinessRuleViolation::make('no_show_too_early', 'reason_code', ['grace_ends_at' => $booking->free_wait_ends_at?->toIso8601ZuluString()]);
        }
    }

    /**
     * GPS + time evidence of a customer no-show report, from the fix the provider app sent (or its last known position).
     *
     * @return array<string, mixed>|null
     */
    private function noShowEvidence(Booking $booking, ProviderProfile $provider, CancelBookingData $data, CarbonImmutable $now): ?array
    {
        return $data->reason === CancellationReason::CustomerNoShow
            ? $this->evidence->build($booking, $provider, $data->fix ?? new ArrivalData, $now)
            : null;
    }

    private function reasonText(CancelBookingData $data): string
    {
        $note = trim((string) $data->note);

        return $note !== '' ? mb_substr($note, 0, (int) config('booking.trip.cancellation_note_max_chars', 255)) : $data->reason->value;
    }

    private function storedDecision(Booking $booking, ActorType $actor): CancellationDecision
    {
        $stored = (array) ($booking->cancellation_decision ?? []);

        return new CancellationDecision(
            ActorType::tryFrom((string) ($stored['actor'] ?? '')) ?? $actor,
            CancellationStage::tryFrom((string) ($stored['stage'] ?? '')) ?? CancellationStage::Searching,
            (int) ($booking->cancellation_fee_minor ?? 0),
            $booking->currency,
            (string) ($stored['basis'] ?? CancellationDecision::BASIS_NO_RULE),
            CancellationFeeRecipient::tryFrom((string) ($stored['recipient'] ?? '')),
            isset($stored['policy_id']) ? (int) $stored['policy_id'] : null,
            isset($stored['grace_ends_at']) ? CarbonImmutable::parse((string) $stored['grace_ends_at']) : null,
            isset($stored['decided_at']) ? CarbonImmutable::parse((string) $stored['decided_at']) : CarbonImmutable::now(),
            $stored['rule']['type'] ?? null,
            isset($stored['rule']['value']) ? (int) $stored['rule']['value'] : null,
            ActorType::tryFrom((string) ($stored['payer'] ?? '')),
            isset($stored['provider_share_bp']) ? (int) $stored['provider_share_bp'] : null,
            isset($stored['penalty']) && is_array($stored['penalty']) ? $stored['penalty'] : null,
            isset($stored['evidence']) && is_array($stored['evidence']) ? $stored['evidence'] : null,
        );
    }
}
