<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingOverrideData;
use App\DTOs\Booking\BookingOverrideResult;
use App\DTOs\Booking\CancellationDecision;
use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingOverrideAction;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CancellationStage;
use App\Enums\Booking\OfferInvalidationReason;
use App\Enums\Booking\OperationsCancellationFee;
use App\Enums\Booking\SupportFlagReason;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Realtime\TripStatusChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Jobs\Booking\ExpireBookingSearch;
use App\Jobs\Dispatch\DispatchBookingWave;
use App\Models\Admin;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Dispatch\EventSequencer;
use App\Services\Finance\BookingSettlementService;
use App\Services\Payments\FreeBookingCompletionService;
use App\Services\Operations\OperationsFeedPublisher;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Operator interventions on a booking (SRS §8.3, §16.6, Plan P2.8): cancel by operations with an explicit fee decision,
 * extend the search window, re-dispatch now, force a status inside the transition graph, and lift a PIN lock.
 *
 * Rules shared by every action: the admin needs the action's own permission, a reason is mandatory, the booking row is
 * locked, an audit entry (before/after, reason, action) is written in the same transaction, and the parties are told
 * through `BookingNotifier` (inbox + push via the notification platform). Status changes go only through `BookingTransitionService`, so the
 * graph (`BookingStatus::allowedTransitions()`) and the guards of §8.3 still apply. Nothing here edits an offer version
 * or a price: offers are only closed (cancel, expire) or left alone.
 */
final class BookingOverrideService
{
    /**
     * Statuses an operator may force a booking into. Selection by an offer and cancellations by the customer or the
     * provider belong to those parties; operations cancels with its own action.
     *
     * @var list<BookingStatus>
     */
    private const FORCE_TARGETS = [
        BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress,
        BookingStatus::DeliveryAttemptFailed, BookingStatus::CompletedPendingPayment, BookingStatus::Completed,
        BookingStatus::Disputed, BookingStatus::ExpiredNoProvider,
    ];

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingOfferRepositoryInterface $offers,
        private readonly DispatchRecipientRepositoryInterface $recipients,
        private readonly BookingTransitionService $transitions,
        private readonly CancellationFeeCalculator $calculator,
        private readonly AuditLogger $audit,
        private readonly BookingNotifier $notifier,
        private readonly OperationsFeedPublisher $feed,
        private readonly EventSequencer $sequencer,
        private readonly BookingSettlementService $settlement,
        private readonly FreeBookingCompletionService $freeCompletion,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * The actions the booking allows right now for this admin (UI buttons; every action re-checks under the lock).
     *
     * @return list<BookingOverrideAction>
     */
    public function availableFor(Booking $booking, Admin $admin): array
    {
        $available = [];

        foreach (BookingOverrideAction::cases() as $action) {
            if ($admin->can($action->permission()->value) && $this->isApplicable($booking, $action)) {
                $available[] = $action;
            }
        }

        return $available;
    }

    /**
     * @return list<BookingStatus>
     */
    public function forceTargets(Booking $booking): array
    {
        return array_values(array_filter(
            self::FORCE_TARGETS,
            static fn (BookingStatus $target): bool => $booking->status->canTransitionTo($target),
        ));
    }

    public function isApplicable(Booking $booking, BookingOverrideAction $action): bool
    {
        return match ($action) {
            BookingOverrideAction::CancelByOperations => $booking->status->canTransitionTo(BookingStatus::CancelledByOperations),
            BookingOverrideAction::ExtendSearch, BookingOverrideAction::RedispatchNow => $booking->status->isSearching(),
            BookingOverrideAction::ForceTransition => $this->forceTargets($booking) !== [],
            BookingOverrideAction::LiftPinLock => $booking->pin_locked_at !== null && $booking->status === BookingStatus::ProviderArrived,
        };
    }

    /**
     * @throws ConflictException `override_not_available`
     * @throws BusinessRuleViolation `override_fee_policy_unavailable`
     */
    public function cancelByOperations(Admin $actor, string $bookingUuid, BookingOverrideData $data): BookingOverrideResult
    {
        return $this->perform($actor, $bookingUuid, BookingOverrideAction::CancelByOperations, $data, function (Booking $booking) use ($actor, $data): array {
            $now = CarbonImmutable::now();
            $fee = $data->fee ?? OperationsCancellationFee::Waive;
            $stage = CancellationStage::of($booking->status);
            $decision = $this->feeDecision($booking, $fee, $stage, $now);

            $this->offers->invalidateActive($booking, OfferInvalidationReason::BookingCancelled);

            $this->transitions->transition($booking, BookingStatus::CancelledByOperations, new TransitionContext(
                actorType: ActorType::Admin,
                actorId: $actor->id,
                reason: $data->reason,
                metadata: ['override' => BookingOverrideAction::CancelByOperations->value, 'fee_decision' => $fee->value, 'cancellation' => $decision?->toArray()],
                at: $now,
                reasonCode: 'operations_override',
                feeDecision: $decision,
            ));

            return ['fee_decision' => $fee->value, 'fee_minor' => $decision?->feeMinor ?? 0];
        });
    }

    /**
     * @throws ConflictException `override_not_available`
     * @throws BusinessRuleViolation `override_extension_invalid`
     */
    public function extendSearch(Admin $actor, string $bookingUuid, BookingOverrideData $data): BookingOverrideResult
    {
        $seconds = $data->extendSeconds ?? (int) config('booking.operations.extend_search_default_seconds', 300);

        if ($seconds < 30 || $seconds > (int) config('booking.operations.extend_search_max_seconds', 1800)) {
            throw BusinessRuleViolation::make('override_extension_invalid', 'extend_seconds', ['min' => 30, 'max' => (int) config('booking.operations.extend_search_max_seconds', 1800)]);
        }

        return $this->perform($actor, $bookingUuid, BookingOverrideAction::ExtendSearch, $data, function (Booking $booking) use ($actor, $data, $seconds): array {
            $now = CarbonImmutable::now();
            $base = $booking->search_expires_at !== null && $booking->search_expires_at->greaterThan($now) ? CarbonImmutable::instance($booking->search_expires_at) : $now;
            $expiresAt = $base->addSeconds($seconds);

            $this->bookings->forceUpdate($booking, ['search_expires_at' => $expiresAt, 'lock_version' => $booking->lock_version + 1]);
            $this->transitions->annotate($booking, 'search_extended_by_operations', $this->context($actor, $data), ['extended_seconds' => $seconds, 'search_expires_at' => $expiresAt->toIso8601String()]);

            // The exact end of the new window, and the waves the old window had not reached (the chain stops at the old end).
            ExpireBookingSearch::dispatch($booking->id)->delay($expiresAt)->afterCommit();
            DispatchBookingWave::dispatch($booking->id, $this->recipients->lastWave($booking->id) + 1, $booking->request_version)->afterCommit();

            return ['extended_seconds' => $seconds, 'search_expires_at' => $expiresAt->toIso8601String()];
        });
    }

    /**
     * Starts dispatch over from wave 1 (every provider may be notified again). Active offers stay as they are.
     *
     * @throws ConflictException `override_not_available`
     */
    public function redispatchNow(Admin $actor, string $bookingUuid, BookingOverrideData $data): BookingOverrideResult
    {
        return $this->perform($actor, $bookingUuid, BookingOverrideAction::RedispatchNow, $data, function (Booking $booking) use ($actor, $data): array {
            $now = CarbonImmutable::now();
            $extended = false;

            // A window about to close would end the search before wave 1 reaches anyone: give it the default extension.
            if ($booking->search_expires_at === null || $booking->search_expires_at->lessThanOrEqualTo($now->addSeconds(30))) {
                $this->bookings->forceUpdate($booking, [
                    'search_expires_at' => $now->addSeconds((int) config('booking.operations.extend_search_default_seconds', 300)),
                    'lock_version' => $booking->lock_version + 1,
                ]);
                $extended = true;
            }

            $this->transitions->annotate($booking, 'redispatch_requested_by_operations', $this->context($actor, $data), ['window_extended' => $extended]);
            BookingEnteredSearch::dispatch($booking->id, restart: true);

            return ['window_extended' => $extended];
        });
    }

    /**
     * @throws BusinessRuleViolation `override_target_invalid`, plus the guard errors of BookingTransitionService
     * @throws ForbiddenActionException `override_forbidden` when the start needs a PIN bypass and the admin lacks `bookings.pin_override`
     */
    public function forceTransition(Admin $actor, string $bookingUuid, BookingOverrideData $data): BookingOverrideResult
    {
        $target = $data->targetStatus ?? throw BusinessRuleViolation::make('override_target_invalid', 'target_status');

        return $this->perform($actor, $bookingUuid, BookingOverrideAction::ForceTransition, $data, function (Booking $booking) use ($actor, $data, $target): array {
            if (! in_array($target, $this->forceTargets($booking), true)) {
                throw BusinessRuleViolation::make('override_target_invalid', 'target_status', ['booking_status' => $booking->status->value]);
            }

            $from = $booking->status;
            $metadata = ['override' => BookingOverrideAction::ForceTransition->value, 'forced' => true];
            $pinVerified = false;

            if ($target === BookingStatus::InProgress && $from === BookingStatus::ProviderArrived
                && (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false) && $booking->pin_verified_at === null) {
                if (! $actor->can(AdminPermission::BookingsPinOverride->value)) {
                    throw ForbiddenActionException::make('override_forbidden');
                }

                $pinVerified = true;
                $metadata['pin_bypassed'] = true;
            }

            if ($target === BookingStatus::ExpiredNoProvider) {
                $this->offers->invalidateActive($booking, OfferInvalidationReason::SearchExpired);
            }

            $moved = $this->transitions->transition($booking, $target, new TransitionContext(
                actorType: ActorType::Admin,
                actorId: $actor->id,
                reason: $data->reason,
                metadata: $metadata,
                pinVerified: $pinVerified,
            ));

            // An operator finishing a trip must leave the same money trail as the provider doing it (P3.1).
            if ($from === BookingStatus::InProgress && ($target === BookingStatus::Completed || $target === BookingStatus::CompletedPendingPayment)) {
                $this->freeCompletion->completeIfNothingToPay($this->settlement->onCompleted($moved));
            }

            return ['from' => $from->value, 'to' => $target->value] + ($pinVerified ? ['pin_bypassed' => true] : []);
        });
    }

    /**
     * Lifts the lock after too many wrong PIN entries: attempts back to zero, the support flag of that reason cleared.
     * The PIN itself is never read or shown here.
     *
     * @throws ConflictException `override_not_available` when the booking is not locked
     */
    public function liftPinLock(Admin $actor, string $bookingUuid, BookingOverrideData $data): BookingOverrideResult
    {
        return $this->perform($actor, $bookingUuid, BookingOverrideAction::LiftPinLock, $data, function (Booking $booking) use ($actor, $data): array {
            $attempts = $booking->pin_attempts;
            $clearsFlag = $booking->support_flag_reason === SupportFlagReason::PinAttemptsExceeded->value;

            $this->bookings->forceUpdate($booking, [
                'pin_locked_at' => null,
                'pin_attempts' => 0,
                'lock_version' => $booking->lock_version + 1,
            ] + ($clearsFlag ? ['support_flagged_at' => null, 'support_flag_reason' => null] : []));

            $this->transitions->annotate($booking, 'pin_lock_lifted', $this->context($actor, $data), ['previous_attempts' => $attempts]);
            $this->announceToParties($booking, ['pin_lock_lifted' => true]);

            return ['previous_attempts' => $attempts, 'flag_cleared' => $clearsFlag];
        });
    }

    /**
     * Runs one action: permission, lock, applicability, the action itself, audit, then the notices after commit.
     *
     * @param Closure(Booking): array<string, mixed> $apply
     */
    private function perform(Admin $actor, string $bookingUuid, BookingOverrideAction $action, BookingOverrideData $data, Closure $apply): BookingOverrideResult
    {
        if (! $actor->can($action->permission()->value)) {
            throw ForbiddenActionException::make('override_forbidden');
        }

        if (trim($data->reason) === '') {
            throw BusinessRuleViolation::make('override_reason_required', 'reason');
        }

        $result = $this->db->transaction(function () use ($actor, $bookingUuid, $action, $data, $apply): BookingOverrideResult {
            $booking = $this->bookings->findByUuidForUpdate($bookingUuid) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

            if (! $this->isApplicable($booking, $action)) {
                throw ConflictException::make('override_not_available', context: ['booking_status' => $booking->status->value]);
            }

            $before = $this->snapshot($booking);
            $summary = $apply($booking);
            $booking->refresh();

            $this->audit->record(
                AuditAction::Overridden,
                $booking,
                $actor,
                $before,
                $this->snapshot($booking),
                $data->reason,
                ['override' => $action->value] + $summary,
            );

            return new BookingOverrideResult($booking, $action, $summary);
        });

        $result->booking->loadMissing(['customer', 'provider']);
        $this->notifier->operationsOverride($result->booking, $action, $result->summary);
        $this->feed->bookingChanged($result->booking->uuid, $result->booking->status->value, null);

        return $result;
    }

    private function context(Admin $actor, BookingOverrideData $data): TransitionContext
    {
        return new TransitionContext(ActorType::Admin, $actor->id, $data->reason);
    }

    private function feeDecision(Booking $booking, OperationsCancellationFee $fee, ?CancellationStage $stage, CarbonImmutable $now): ?CancellationDecision
    {
        if ($stage === null) {
            // In progress: the policy has no stage for it, so only a waiver is possible.
            if ($fee === OperationsCancellationFee::ApplyPolicy) {
                throw BusinessRuleViolation::make('override_fee_policy_unavailable', 'fee');
            }

            return null;
        }

        if ($fee === OperationsCancellationFee::Waive) {
            return new CancellationDecision(ActorType::Admin, $stage, 0, $booking->currency, CancellationDecision::BASIS_WAIVED_BY_OPERATIONS, null, null, null, $now);
        }

        $policy = $this->calculator->decide($booking, ActorType::Customer, $now, null);

        return new CancellationDecision(
            ActorType::Admin, $policy->stage, $policy->feeMinor, $policy->currency, CancellationDecision::BASIS_OPERATIONS_POLICY,
            $policy->recipient, $policy->policyId, $policy->graceEndsAt, $now, $policy->ruleType, $policy->ruleValue,
            payer: ActorType::Customer, providerShareBp: $policy->providerShareBp,
        );
    }

    /**
     * The apps refetch the booking on a status event; a same-status event carries the news that something else changed.
     *
     * @param array<string, mixed> $extra
     */
    private function announceToParties(Booking $booking, array $extra): void
    {
        $booking->loadMissing(['customer', 'provider']);
        $payload = ['status' => $booking->status->value, 'previous_status' => $booking->status->value, 'request_version' => $booking->request_version, 'actor' => ActorType::Admin->value] + $extra;
        $sequence = $this->sequencer->next($booking->uuid);

        TripStatusChanged::toCustomer($booking->customer->uuid, $booking->uuid, $sequence, $payload);

        if ($booking->provider !== null) {
            TripStatusChanged::toProvider($booking->provider->uuid, $booking->uuid, $sequence, $payload);
        }
    }

    /**
     * What the audit entry stores: no contact data, no addresses, no PIN.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Booking $booking): array
    {
        return [
            'status' => $booking->status->value,
            'provider_id' => $booking->provider_id,
            'search_expires_at' => $booking->search_expires_at?->toIso8601String(),
            'pin_attempts' => $booking->pin_attempts,
            'pin_locked_at' => $booking->pin_locked_at?->toIso8601String(),
            'support_flag_reason' => $booking->support_flag_reason,
            'cancellation_fee_minor' => $booking->cancellation_fee_minor,
            'agreed_fare_minor' => $booking->agreed_fare_minor,
        ];
    }
}
