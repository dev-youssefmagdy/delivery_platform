<?php

namespace App\Services\Courier;

use App\DTOs\Booking\TransitionContext;
use App\DTOs\Courier\DeliveryAttemptData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Booking\DeliveryAttemptResolution;
use App\Enums\Booking\SupportFlagReason;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\Admin;
use App\Models\DeliveryAttempt;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingTransitionService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * A hand-over that did not happen (SRS §8.1 `delivery_attempt_failed`, §20.4).
 *
 * The provider reports it from the drop-off (`in_progress` + custody `arrived_dropoff`): an outcome, a reason when the outcome
 * needs words, and optionally a picture as evidence. The booking moves to `delivery_attempt_failed` through
 * `BookingTransitionService`, custody becomes `attempt_failed`, and the customer is asked to choose. The attempt is then
 * resolved exactly once, by the customer, the provider or operations:
 *
 * - retry: back to `in_progress` with custody `in_transit` (only while attempts are left, `courier.attempts.max_per_booking`);
 * - return_to_sender: back to `in_progress` with custody `returning`; the way back ends with a return photo, the booking
 *   completes at the agreed fare (the failure was not the provider's) and custody becomes `returned`;
 * - operations: the booking stays in `delivery_attempt_failed`, is flagged for support, and an operator decides with the
 *   audited overrides (cancel with fee decision, force transition, dispute). An attempt nobody resolved is escalated this way
 *   after `courier.attempts.escalate_after_minutes`.
 */
final class DeliveryAttemptService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly DeliveryAttemptRepositoryInterface $attempts,
        private readonly BookingTransitionService $transitions,
        private readonly CustodyService $custody,
        private readonly DeliveryProofService $proofs,
        private readonly ProofFileStore $files,
        private readonly BookingNotifier $notifier,
        private readonly DatabaseManager $db,
    ) {}

    public static function maxAttempts(): int
    {
        return max(1, (int) config('courier.attempts.max_per_booking', 3));
    }

    /**
     * @return array{attempt: DeliveryAttempt, booking: Booking, replayed: bool}
     *
     * @throws BusinessRuleViolation `delivery_attempts_not_supported`, `delivery_attempt_reason_required`
     * @throws ConflictException `delivery_attempt_not_allowed`, `custody_not_at_dropoff`
     */
    public function report(ProviderProfile $provider, string $bookingUuid, DeliveryAttemptData $data): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if (! CustodyService::applies($booking)) {
            throw BusinessRuleViolation::make('delivery_attempts_not_supported');
        }

        $reason = $data->reason === null ? null : trim($data->reason);
        $reason = $reason === '' ? null : $reason;

        if ($reason === null && $data->outcome->needsReason()) {
            throw BusinessRuleViolation::make('delivery_attempt_reason_required', 'reason');
        }

        if ($reason !== null && mb_strlen($reason) > (int) config('courier.attempts.reason_max_chars', 500)) {
            throw BusinessRuleViolation::make('delivery_attempt_reason_too_long', 'reason', ['max' => (int) config('courier.attempts.reason_max_chars', 500)]);
        }

        $stored = $data->evidence === null ? null : $this->files->store($data->evidence, $booking, \App\Enums\Catalog\ProofStage::Delivery);

        try {
            return $this->db->transaction(function () use ($provider, $booking, $data, $reason, $stored): array {
                $locked = $this->bookings->lockById($booking->id);

                if (! $locked instanceof Booking || $locked->provider_id !== $provider->id) {
                    throw (new ModelNotFoundException)->setModel(Booking::class);
                }

                if ($locked->status === BookingStatus::DeliveryAttemptFailed) {
                    $latest = $this->attempts->latestFor($locked->id);

                    if ($latest instanceof DeliveryAttempt && $latest->isPending()) {
                        $this->discard($stored);

                        return ['attempt' => $latest, 'booking' => $locked, 'replayed' => true];
                    }
                }

                if ($locked->status !== BookingStatus::InProgress) {
                    throw ConflictException::make('delivery_attempt_not_allowed', context: ['booking_status' => $locked->status->value]);
                }

                if ($locked->custody_status !== CustodyStatus::ArrivedDropoff) {
                    throw ConflictException::make('custody_not_at_dropoff', context: ['custody' => $locked->custody_status?->value]);
                }

                $number = $locked->delivery_attempts_count + 1;
                $evidence = $stored === null ? null : $this->proofs->recordEvidence($locked, $provider, $stored, ['delivery_attempt' => $number, 'outcome' => $data->outcome->value]);

                $attempt = $this->attempts->create([
                    'booking_id' => $locked->id,
                    'provider_id' => $provider->id,
                    'attempt_number' => $number,
                    'outcome' => $data->outcome,
                    'reason' => $reason,
                    'evidence_proof_id' => $evidence?->id,
                ]);

                $context = new TransitionContext(ActorType::Provider, $provider->id, 'delivery_attempt_failed', ['outcome' => $data->outcome->value, 'attempt' => $number]);
                $moved = $this->transitions->transition($locked, BookingStatus::DeliveryAttemptFailed, $context);
                $this->bookings->forceUpdate($moved, ['delivery_attempts_count' => $number, 'lock_version' => $moved->lock_version + 1]);
                $this->custody->move($moved->refresh(), CustodyStatus::AttemptFailed, $context, ['outcome' => $data->outcome->value, 'attempt' => $number]);
                $this->notifier->deliveryAttemptFailed($moved->refresh(), $attempt->uuid);

                return ['attempt' => $attempt, 'booking' => $moved->refresh(), 'replayed' => false];
            });
        } catch (Throwable $e) {
            $this->discard($stored);

            throw $e;
        }
    }

    /**
     * @return array{attempt: DeliveryAttempt, booking: Booking, replayed: bool}
     */
    public function resolveByProvider(ProviderProfile $provider, string $bookingUuid, string $attemptUuid, DeliveryAttemptResolution $resolution): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->resolve($booking, $attemptUuid, $resolution, ActorType::Provider, $provider->id, TransitionContext::provider($provider));
    }

    /**
     * @return array{attempt: DeliveryAttempt, booking: Booking, replayed: bool}
     */
    public function resolveByCustomer(User $customer, string $bookingUuid, string $attemptUuid, DeliveryAttemptResolution $resolution): array
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->resolve($booking, $attemptUuid, $resolution, ActorType::Customer, $customer->id, TransitionContext::customer($customer));
    }

    /**
     * Operations resolves the pending attempt (retry / return to sender / escalate). Runs inside the operator override
     * transaction (`BookingOverrideService`), which owns the permission, the mandatory reason, the audit entry and the notices.
     *
     * @return array{attempt: DeliveryAttempt, booking: Booking, replayed: bool}
     */
    public function resolveByOperator(Admin $admin, Booking $booking, string $attemptUuid, DeliveryAttemptResolution $resolution, string $reason): array
    {
        return $this->resolve($booking, $attemptUuid, $resolution, ActorType::Admin, $admin->id, new TransitionContext(ActorType::Admin, $admin->id, $reason));
    }

    /**
     * Escalates attempts nobody resolved in time to operations. Returns how many were escalated.
     */
    public function escalateStale(?CarbonImmutable $now = null, int $limit = 200): int
    {
        $now ??= CarbonImmutable::now();
        $before = $now->subMinutes((int) config('courier.attempts.escalate_after_minutes', 30));
        $escalated = 0;

        foreach ($this->attempts->bookingIdsWithPendingBefore($before, $limit) as $bookingId) {
            $booking = $this->bookings->find($bookingId);

            if (! $booking instanceof Booking) {
                continue;
            }

            $latest = $this->attempts->latestFor($booking->id);

            if ($latest instanceof DeliveryAttempt && $latest->isPending()) {
                $this->resolve($booking, $latest->uuid, DeliveryAttemptResolution::Operations, ActorType::System, null, TransitionContext::system('delivery_attempt_escalated'));
                $escalated++;
            }
        }

        return $escalated;
    }

    /**
     * @return array{attempt: DeliveryAttempt, booking: Booking, replayed: bool}
     */
    private function resolve(Booking $booking, string $attemptUuid, DeliveryAttemptResolution $resolution, ActorType $by, ?int $byId, TransitionContext $context): array
    {
        return $this->db->transaction(function () use ($booking, $attemptUuid, $resolution, $by, $byId, $context): array {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking || ! $this->owns($locked, $by, $byId)) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            $attempt = $this->attempts->lockInBooking($locked->id, $attemptUuid) ?? throw (new ModelNotFoundException)->setModel(DeliveryAttempt::class);

            if (! $attempt->isPending()) {
                if ($attempt->resolution === $resolution) {
                    return ['attempt' => $attempt, 'booking' => $locked, 'replayed' => true];
                }

                throw ConflictException::make('delivery_attempt_already_resolved', context: ['resolution' => $attempt->resolution?->value]);
            }

            if ($locked->status !== BookingStatus::DeliveryAttemptFailed) {
                throw ConflictException::make('delivery_attempt_not_allowed', context: ['booking_status' => $locked->status->value]);
            }

            $moved = match ($resolution) {
                DeliveryAttemptResolution::Retry => $this->back($locked, $context, CustodyStatus::InTransit, 'delivery_retry', retry: true),
                DeliveryAttemptResolution::ReturnToSender => $this->back($locked, $context, CustodyStatus::Returning, 'delivery_return_to_sender', retry: false),
                DeliveryAttemptResolution::Operations => $this->escalate($locked, $context),
            };

            $attempt = $this->attempts->update($attempt, [
                'resolution' => $resolution,
                'resolved_by_type' => $by->value,
                'resolved_by_id' => $byId,
                'resolved_at' => CarbonImmutable::now(),
            ]);

            if ($by !== ActorType::System && $by !== ActorType::Admin) {
                $this->notifier->deliveryAttemptResolved($moved, $attempt->uuid, $by);
            }

            return ['attempt' => $attempt, 'booking' => $moved->refresh(), 'replayed' => false];
        });
    }

    private function back(Booking $locked, TransitionContext $context, CustodyStatus $custody, string $reason, bool $retry): Booking
    {
        if ($retry && $locked->delivery_attempts_count >= self::maxAttempts()) {
            throw BusinessRuleViolation::make('delivery_attempts_exhausted', 'resolution', ['max' => self::maxAttempts()]);
        }

        $moved = $this->transitions->transition($locked, BookingStatus::InProgress, new TransitionContext(
            $context->actorType,
            $context->actorId,
            $reason,
            ['attempt' => $locked->delivery_attempts_count],
        ));
        $this->custody->move($moved->refresh(), $custody, $context);

        return $moved->refresh();
    }

    private function escalate(Booking $locked, TransitionContext $context): Booking
    {
        $this->bookings->forceUpdate($locked, [
            'support_flagged_at' => CarbonImmutable::now(),
            'support_flag_reason' => SupportFlagReason::DeliveryAttemptEscalated->value,
            'lock_version' => $locked->lock_version + 1,
        ]);
        $this->transitions->annotate($locked, 'delivery_attempt_escalated', $context, ['attempt' => $locked->delivery_attempts_count]);

        return $locked->refresh();
    }

    private function owns(Booking $booking, ActorType $by, ?int $byId): bool
    {
        return match ($by) {
            ActorType::Provider => $booking->provider_id === $byId,
            ActorType::Customer => $booking->customer_id === $byId,
            default => true,
        };
    }

    private function discard(?StoredProofFile $file): void
    {
        if ($file instanceof StoredProofFile) {
            $this->files->discard($file);
        }
    }
}
