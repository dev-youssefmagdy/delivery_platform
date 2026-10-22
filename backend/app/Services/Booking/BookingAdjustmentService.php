<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingAdjustmentData;
use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingAdjustmentStatus;
use App\Enums\Booking\BookingAdjustmentType;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Events\Realtime\BookingAdjustmentChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingAdjustmentRequest;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Fare adjustments after selection (SRS §9.4, Plan P3.5). The fare agreed at selection never changes unilaterally: one
 * party asks (extra stop, waiting, toll / parking at cost with evidence, scope change), the OTHER party approves in the
 * app, and only approved adjustments enter the price. Everything runs in one transaction under the booking lock:
 *
 * - `adjustments_total_minor` on the booking is the signed sum of approved adjustments; completion sets
 *   `final_fare_minor = agreed + total` (BookingTransitionService), so the breakdown, the ledger and the receipt all use it.
 * - A request is allowed from the selection to the trip in progress (not after completion or cancellation), is capped
 *   (open requests per booking, one open request per type, total increase as a share of the agreed fare) and expires.
 * - Waiting charges: after the free wait the server prices `ceil(minutes) x waiting_rate_minor` of the booking's policy
 *   snapshot; with no rate the provider proposes an amount that the customer may refuse.
 * - Every step annotates the booking timeline and notifies both parties' channels (`booking.adjustment_changed`).
 * Replays (same Idempotency-Key, or an answer to an already answered request by the same party) return the stored result.
 */
final class BookingAdjustmentService
{
    /**
     * Statuses in which a fare may still be adjusted.
     *
     * @var list<BookingStatus>
     */
    private const OPEN_STATUSES = [BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress];

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingAdjustmentRepositoryInterface $adjustments,
        private readonly BookingTransitionService $transitions,
        private readonly BookingNotifier $notifier,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation|ConflictException
     */
    public function requestByCustomer(User $customer, string $bookingUuid, BookingAdjustmentData $data): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->request($booking, ActorType::Customer, $customer->id, $data);
    }

    /**
     * @throws BusinessRuleViolation|ConflictException
     */
    public function requestByProvider(ProviderProfile $provider, string $bookingUuid, BookingAdjustmentData $data): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->request($booking, ActorType::Provider, $provider->id, $data);
    }

    /**
     * @return \Illuminate\Support\Collection<int, BookingAdjustmentRequest>
     */
    public function listForCustomer(User $customer, string $bookingUuid): \Illuminate\Support\Collection
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->adjustments->forBooking($booking->id);
    }

    /**
     * @return \Illuminate\Support\Collection<int, BookingAdjustmentRequest>
     */
    public function listForProvider(ProviderProfile $provider, string $bookingUuid): \Illuminate\Support\Collection
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->adjustments->forBooking($booking->id);
    }

    public function approveByCustomer(User $customer, string $bookingUuid, string $adjustmentUuid, ?string $note): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Customer, $customer->id, BookingAdjustmentStatus::Approved, $note);
    }

    public function approveByProvider(ProviderProfile $provider, string $bookingUuid, string $adjustmentUuid, ?string $note): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Provider, $provider->id, BookingAdjustmentStatus::Approved, $note);
    }

    public function rejectByCustomer(User $customer, string $bookingUuid, string $adjustmentUuid, ?string $note): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Customer, $customer->id, BookingAdjustmentStatus::Rejected, $note);
    }

    public function rejectByProvider(ProviderProfile $provider, string $bookingUuid, string $adjustmentUuid, ?string $note): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Provider, $provider->id, BookingAdjustmentStatus::Rejected, $note);
    }

    public function withdrawByCustomer(User $customer, string $bookingUuid, string $adjustmentUuid): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Customer, $customer->id, BookingAdjustmentStatus::Withdrawn, null);
    }

    public function withdrawByProvider(ProviderProfile $provider, string $bookingUuid, string $adjustmentUuid): BookingAdjustmentRequest
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->answer($booking, $adjustmentUuid, ActorType::Provider, $provider->id, BookingAdjustmentStatus::Withdrawn, null);
    }

    /**
     * Operations void a request. A pending one is simply closed; an approved one is taken out of the total, which is only
     * possible while the booking is not completed (a finished trip is corrected through a refund or a wallet adjustment).
     *
     * @throws ConflictException `adjustment_not_voidable`
     */
    public function voidByAdmin(Admin $admin, BookingAdjustmentRequest $adjustment, string $reason): BookingAdjustmentRequest
    {
        return $this->db->transaction(function () use ($admin, $adjustment, $reason): BookingAdjustmentRequest {
            $booking = $this->bookings->lockById($adjustment->booking_id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
            $locked = $this->adjustments->lockByUuid($adjustment->uuid) ?? throw (new ModelNotFoundException)->setModel(BookingAdjustmentRequest::class);

            if ($locked->status === BookingAdjustmentStatus::Voided) {
                return $locked;
            }

            $wasApproved = $locked->status === BookingAdjustmentStatus::Approved;

            if (! $locked->status->isOpen() && ! $wasApproved) {
                throw ConflictException::make('adjustment_not_voidable');
            }

            if ($wasApproved) {
                if (! in_array($booking->status, self::OPEN_STATUSES, true)) {
                    throw ConflictException::make('adjustment_not_voidable');
                }

                $this->bookings->forceUpdate($booking, ['adjustments_total_minor' => $booking->adjustments_total_minor - $locked->amount_minor]);
            }

            $before = $locked->auditSnapshot();
            $this->adjustments->update($locked, ['status' => BookingAdjustmentStatus::Voided, 'decided_by_type' => ActorType::Admin->value, 'decided_by_id' => $admin->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => mb_substr($reason, 0, 500)]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $admin, $before, $locked->auditSnapshot(), $reason);
            $this->publish($booking, $locked, 'adjustment_voided', new TransitionContext(ActorType::Admin, $admin->id, $reason));

            return $locked;
        });
    }

    /**
     * Closes every open request of a booking and voids the approved ones (the agreement they belonged to ended, for
     * example the provider cancelled and the booking went back to search). Call inside the booking transaction.
     */
    public function closeAll(Booking $booking, string $reason, CarbonImmutable $now): void
    {
        foreach ($this->adjustments->forBooking($booking->id) as $adjustment) {
            $status = match ($adjustment->status) {
                BookingAdjustmentStatus::Pending => BookingAdjustmentStatus::Expired,
                BookingAdjustmentStatus::Approved => BookingAdjustmentStatus::Voided,
                default => null,
            };

            if ($status !== null) {
                $this->adjustments->update($adjustment, ['status' => $status, 'decided_by_type' => ActorType::System->value, 'decided_at' => $now, 'decision_note' => $reason]);
            }
        }
    }

    /**
     * Expires the pending requests of a booking that is ending (completion): approved ones stay as decided. Call inside
     * the booking transaction.
     */
    public function expireOpen(Booking $booking, string $reason, CarbonImmutable $now): void
    {
        foreach ($this->adjustments->forBooking($booking->id) as $adjustment) {
            if ($adjustment->status->isOpen()) {
                $this->adjustments->update($adjustment, ['status' => BookingAdjustmentStatus::Expired, 'decided_by_type' => ActorType::System->value, 'decided_at' => $now, 'decision_note' => $reason]);
            }
        }
    }

    /**
     * Expires pending requests whose time ran out (scheduler, idempotent). Returns how many were closed.
     */
    public function expireDue(CarbonImmutable $now, int $limit = 200): int
    {
        $closed = 0;

        foreach ($this->adjustments->expiredPending($now, $limit) as $due) {
            $closed += $this->db->transaction(function () use ($due, $now): int {
                $booking = $this->bookings->lockById($due->booking_id);
                $locked = $this->adjustments->lockByUuid($due->uuid);

                if (! $booking instanceof Booking || ! $locked instanceof BookingAdjustmentRequest || $locked->status !== BookingAdjustmentStatus::Pending) {
                    return 0;
                }

                $this->adjustments->update($locked, ['status' => BookingAdjustmentStatus::Expired, 'decided_by_type' => ActorType::System->value, 'decided_at' => $now]);
                $this->publish($booking, $locked, 'adjustment_expired', TransitionContext::system('adjustment_expired'));

                return 1;
            });
        }

        return $closed;
    }

    private function request(Booking $booking, ActorType $party, int $partyId, BookingAdjustmentData $data): BookingAdjustmentRequest
    {
        if ($data->idempotencyKey !== null) {
            $replay = $this->adjustments->findByIdempotencyKey($booking->id, $party->value, $partyId, $data->idempotencyKey);

            if ($replay instanceof BookingAdjustmentRequest) {
                return $replay;
            }
        }

        return $this->db->transaction(function () use ($booking, $party, $partyId, $data): BookingAdjustmentRequest {
            $locked = $this->bookings->lockById($booking->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
            $now = CarbonImmutable::now();

            $this->assertOpen($locked);

            if (! in_array($party, $data->type->requesters(), true)) {
                throw BusinessRuleViolation::make('adjustment_type_not_allowed', 'type');
            }

            if ($data->type === BookingAdjustmentType::Waiting && ($locked->arrived_at === null || ! in_array($locked->status, [BookingStatus::ProviderArrived, BookingStatus::InProgress], true))) {
                throw BusinessRuleViolation::make('adjustment_waiting_needs_arrival', 'type');
            }

            if ($data->type->requiresEvidence() && trim((string) ($data->evidence['reference'] ?? '')) === '') {
                throw BusinessRuleViolation::make('adjustment_evidence_required', 'evidence.reference');
            }

            [$amount, $details] = $this->priceOf($locked, $data, $now);
            $this->assertAmount($locked, $data->type, $amount);

            $open = $this->adjustments->openForBooking($locked->id);

            if ($open->count() >= (int) config('booking.adjustments.max_open', 3) || $open->contains(static fn (BookingAdjustmentRequest $request): bool => $request->type === $data->type)) {
                throw ConflictException::make('adjustment_open_limit');
            }

            $old = (int) $locked->agreed_fare_minor + $locked->adjustments_total_minor;
            $request = $this->adjustments->insert([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'booking_id' => $locked->id,
                'type' => $data->type,
                'status' => BookingAdjustmentStatus::Pending,
                'requester_type' => $party->value,
                'requester_id' => $partyId,
                'currency' => $locked->currency,
                'old_total_minor' => $old,
                'new_total_minor' => $old + $amount,
                'amount_minor' => $amount,
                'reason' => $data->reason,
                'evidence' => $data->evidence === [] ? null : $data->evidence,
                'details' => $details === [] ? null : $details,
                'expires_at' => $now->addSeconds((int) config('booking.adjustments.ttl_seconds', 600)),
                'idempotency_key' => $data->idempotencyKey,
            ]);

            $this->publish($locked, $request, 'adjustment_requested', new TransitionContext($party, $partyId, $data->type->value, at: $now));

            return $request;
        });
    }

    private function answer(Booking $booking, string $adjustmentUuid, ActorType $party, int $partyId, BookingAdjustmentStatus $target, ?string $note): BookingAdjustmentRequest
    {
        return $this->db->transaction(function () use ($booking, $adjustmentUuid, $party, $partyId, $target, $note): BookingAdjustmentRequest {
            $locked = $this->bookings->lockById($booking->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
            $request = $this->adjustments->lockByUuid($adjustmentUuid);

            if (! $request instanceof BookingAdjustmentRequest || $request->booking_id !== $locked->id) {
                throw (new ModelNotFoundException)->setModel(BookingAdjustmentRequest::class);
            }

            $isRequester = $request->requester_type === $party->value;

            // Only the other party approves or rejects; only the requester withdraws.
            if ($target === BookingAdjustmentStatus::Withdrawn ? ! $isRequester : $isRequester) {
                throw ForbiddenActionException::make($target === BookingAdjustmentStatus::Withdrawn ? 'adjustment_not_yours' : 'adjustment_needs_counterpart');
            }

            if ($request->status === $target) {
                return $request;
            }

            $now = CarbonImmutable::now();

            if (! $request->status->isOpen()) {
                throw ConflictException::make('adjustment_not_pending', context: ['status' => $request->status->value]);
            }

            if ($request->expires_at->lessThanOrEqualTo($now)) {
                $this->adjustments->update($request, ['status' => BookingAdjustmentStatus::Expired, 'decided_by_type' => ActorType::System->value, 'decided_at' => $now]);
                $this->publish($locked, $request, 'adjustment_expired', TransitionContext::system('adjustment_expired'));

                throw ConflictException::make('adjustment_expired');
            }

            $changes = ['status' => $target, 'decided_by_type' => $party->value, 'decided_by_id' => $partyId, 'decided_at' => $now, 'decision_note' => $note === null ? null : mb_substr($note, 0, 500)];

            if ($target === BookingAdjustmentStatus::Approved) {
                $this->assertOpen($locked);
                $old = (int) $locked->agreed_fare_minor + $locked->adjustments_total_minor;
                $this->assertAmount($locked, $request->type, $request->amount_minor);
                $changes += ['old_total_minor' => $old, 'new_total_minor' => $old + $request->amount_minor];
                $this->bookings->forceUpdate($locked, ['adjustments_total_minor' => $locked->adjustments_total_minor + $request->amount_minor]);
            }

            $this->adjustments->update($request, $changes);
            $this->publish($locked, $request, 'adjustment_'.$target->value, new TransitionContext($party, $partyId, $target->value, at: $now));

            return $request;
        });
    }

    /**
     * @throws ConflictException `adjustment_not_allowed_now`
     */
    private function assertOpen(Booking $booking): void
    {
        if (! in_array($booking->status, self::OPEN_STATUSES, true) || $booking->agreed_fare_minor === null) {
            throw ConflictException::make('adjustment_not_allowed_now', context: ['booking_status' => $booking->status->value]);
        }
    }

    /**
     * The signed change in minor units and the details to store. A waiting adjustment without an amount is priced from
     * the policy snapshot's per-minute rate and the minutes past the free wait.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function priceOf(Booking $booking, BookingAdjustmentData $data, CarbonImmutable $now): array
    {
        $details = $data->details;

        if ($data->type === BookingAdjustmentType::Waiting) {
            $rate = (int) ($booking->policy_snapshot['cancellation']['waiting_rate_minor'] ?? 0);
            $from = $booking->free_wait_ends_at ?? $booking->arrived_at;
            $minutes = $from === null || $from->greaterThanOrEqualTo($now) ? 0 : (int) ceil($from->diffInSeconds($now) / 60);
            $details += ['billable_minutes' => $minutes, 'rate_minor' => $rate];

            if ($data->amountMinor === null) {
                if ($rate <= 0 || $minutes <= 0) {
                    throw BusinessRuleViolation::make('adjustment_amount_required', 'amount_minor');
                }

                return [$rate * $minutes, $details];
            }
        }

        if ($data->amountMinor === null || $data->amountMinor === 0) {
            throw BusinessRuleViolation::make('adjustment_amount_required', 'amount_minor');
        }

        return [$data->amountMinor, $details];
    }

    /**
     * Only a scope change may lower the fare; the total never goes below zero or above the agreed fare plus the cap.
     */
    private function assertAmount(Booking $booking, BookingAdjustmentType $type, int $amount): void
    {
        if ($amount < 0 && $type !== BookingAdjustmentType::ScopeChange) {
            throw BusinessRuleViolation::make('adjustment_amount_invalid', 'amount_minor');
        }

        $agreed = (int) $booking->agreed_fare_minor;
        $new = $agreed + $booking->adjustments_total_minor + $amount;
        $ceiling = $agreed + intdiv($agreed * (int) config('booking.adjustments.max_increase_basis_points', 5000), 10_000);

        if ($new < 0 || $new > $ceiling) {
            throw BusinessRuleViolation::make('adjustment_amount_invalid', 'amount_minor', ['max_total_minor' => $ceiling]);
        }
    }

    /**
     * Timeline entry, notification (inbox + push) and the realtime event to both parties.
     */
    private function publish(Booking $booking, BookingAdjustmentRequest $adjustment, string $event, TransitionContext $context): void
    {
        $annotation = $this->transitions->annotate($booking, $event, $context, [
            'adjustment' => $adjustment->uuid,
            'type' => $adjustment->type->value,
            'amount_minor' => $adjustment->amount_minor,
            'status' => $adjustment->status->value,
        ]);

        $booking->loadMissing(['customer', 'provider']);
        $payload = ['adjustment_uuid' => $adjustment->uuid, 'event' => $event, 'status' => $adjustment->status->value, 'type' => $adjustment->type->value, 'requester' => $adjustment->requester_type, 'amount_minor' => $adjustment->amount_minor, 'currency' => $adjustment->currency, 'adjustments_total_minor' => $booking->adjustments_total_minor];

        BookingAdjustmentChanged::toCustomer($booking->customer->uuid, $booking->uuid, $annotation->sequence, $payload);

        if ($booking->provider !== null) {
            BookingAdjustmentChanged::toProvider($booking->provider->uuid, $booking->uuid, $annotation->sequence, $payload);
        }

        $this->notifier->adjustmentChanged($booking, $adjustment, $event);
    }
}
