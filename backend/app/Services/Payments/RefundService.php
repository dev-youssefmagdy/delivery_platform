<?php

namespace App\Services\Payments;

use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\DTOs\Payments\PaymentActor;
use App\DTOs\Payments\RefundRequestData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\RefundStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Payments\Data\GatewayRefundResult;
use App\Integrations\Payments\Data\RefundRequest;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Coupon\CouponReservationService;
use App\Services\Finance\LedgerAccountResolver;
use App\Services\Finance\LedgerService;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * Refunds of collected online payments (SRS §12, Plan P3.2 step 6).
 *
 * - An admin with `refunds.request` asks for an amount with a mandatory reason. Below the dual-approval threshold setting
 *   the requester's authority is enough; at or above it a SECOND admin with `refunds.approve` must approve (never the
 *   requester). Every step is audited with the reason.
 * - Approval sends the refund to the gateway. The ledger is touched only when the gateway confirms (immediately or by its
 *   verified webhook): then a compensating entry (RefundPostingBuilder) is posted, never an edit of the settlement entry.
 * - The refunded amount is bounded: completed + pending refunds can never exceed what was collected.
 * - Locks are taken payment first, then refund (the webhook path does the same), so the two paths cannot deadlock.
 */
final class RefundService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentRefundRepositoryInterface $refunds,
        private readonly PaymentTransitionService $transitions,
        private readonly PaymentGatewayManager $gateways,
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly RefundPostingBuilder $postings,
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalLineRepositoryInterface $lines,
        private readonly BookingRepositoryInterface $bookings,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
        private readonly CouponReservationService $coupons,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation the payment cannot be refunded or the amount is out of range
     */
    public function request(Admin $actor, Payment $payment, RefundRequestData $data): PaymentRefund
    {
        $existing = $this->refunds->findByIdempotencyKey($data->idempotencyKey);

        if ($existing instanceof PaymentRefund) {
            if ($existing->payment_id !== $payment->id) {
                throw BusinessRuleViolation::make('idempotency_key_reused');
            }

            return $existing;
        }

        try {
            return $this->db->transaction(fn (): PaymentRefund => $this->requestLocked($actor, $payment, $data));
        } catch (UniqueConstraintViolationException) {
            return $this->refunds->findByIdempotencyKey($data->idempotencyKey) ?? throw BusinessRuleViolation::make('idempotency_key_reused');
        }
    }

    public function approve(Admin $actor, PaymentRefund $refund, ?string $note): PaymentRefund
    {
        return $this->db->transaction(function () use ($actor, $refund, $note): PaymentRefund {
            $payment = $this->lockPayment($refund->payment_id);
            $locked = $this->refunds->lockById($refund->id) ?? throw (new ModelNotFoundException)->setModel(PaymentRefund::class);

            if ($locked->status !== RefundStatus::PendingApproval) {
                throw ConflictException::make('refund_not_pending');
            }

            if (! $actor->can(AdminPermission::RefundsApprove->value)) {
                throw ForbiddenActionException::make('refund_approval_forbidden');
            }

            if ($locked->requested_by_id === $actor->id) {
                throw ForbiddenActionException::make('refund_self_approval');
            }

            $this->refunds->update($locked, ['status' => RefundStatus::Approved, 'decided_by_id' => $actor->id, 'decided_at' => CarbonImmutable::now(), 'decision_reason' => $note]);
            $this->transitions->note($payment, PaymentEventType::RefundApproved, PaymentActor::admin($actor), ['refund' => $locked->uuid, 'amount_minor' => $locked->amount_minor]);
            $this->audit->record(AuditAction::Approved, $locked, $actor, after: ['status' => RefundStatus::Approved->value, 'amount_minor' => $locked->amount_minor], reason: $note);

            return $this->execute($payment, $locked, PaymentActor::admin($actor));
        });
    }

    public function reject(Admin $actor, PaymentRefund $refund, string $reason): PaymentRefund
    {
        return $this->db->transaction(function () use ($actor, $refund, $reason): PaymentRefund {
            $payment = $this->lockPayment($refund->payment_id);
            $locked = $this->refunds->lockById($refund->id) ?? throw (new ModelNotFoundException)->setModel(PaymentRefund::class);

            if ($locked->status !== RefundStatus::PendingApproval) {
                throw ConflictException::make('refund_not_pending');
            }

            if (! $actor->can(AdminPermission::RefundsApprove->value)) {
                throw ForbiddenActionException::make('refund_approval_forbidden');
            }

            $this->refunds->update($locked, ['status' => RefundStatus::Rejected, 'decided_by_id' => $actor->id, 'decided_at' => CarbonImmutable::now(), 'decision_reason' => $reason]);
            $this->transitions->note($payment, PaymentEventType::RefundRejected, PaymentActor::admin($actor), ['refund' => $locked->uuid]);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, after: ['status' => RefundStatus::Rejected->value], reason: $reason);

            return $locked;
        });
    }

    /**
     * The gateway reported the outcome of a refund (verified webhook or query). Returns null when no refund of this payment
     * matches the event (the webhook is then `unmatched`). Caller holds the payment lock.
     */
    public function applyGatewayResult(Payment $payment, ?string $refundReference, bool $succeeded, ?string $failureCode, PaymentActor $actor): ?PaymentRefund
    {
        $refund = $refundReference !== null ? $this->refunds->findByGatewayReference($payment->id, $refundReference) : null;

        if (! $refund instanceof PaymentRefund) {
            $processing = $this->refunds->processingForPayment($payment->id);
            $refund = $processing->count() === 1 ? $processing->first() : null;
        }

        if (! $refund instanceof PaymentRefund) {
            return null;
        }

        $locked = $this->refunds->lockById($refund->id) ?? throw (new ModelNotFoundException)->setModel(PaymentRefund::class);

        if (! in_array($locked->status, [RefundStatus::Processing, RefundStatus::Approved], true)) {
            return $locked;
        }

        if ($refundReference !== null && $locked->gateway_reference === null) {
            $this->refunds->update($locked, ['gateway_reference' => $refundReference]);
        }

        return $succeeded ? $this->complete($payment, $locked, $actor) : $this->fail($payment, $locked, $failureCode ?? 'refund_declined', $actor);
    }

    private function requestLocked(Admin $actor, Payment $payment, RefundRequestData $data): PaymentRefund
    {
        $locked = $this->lockPayment($payment->id);

        if ($locked->gateway === null || ! in_array($locked->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
            throw BusinessRuleViolation::make('refund_payment_not_refundable');
        }

        if ($data->amountMinor <= 0) {
            throw BusinessRuleViolation::make('refund_amount_invalid', 'amount');
        }

        $available = $locked->amount_minor - $this->refunds->reservedMinor($locked->id);

        if ($data->amountMinor > $available) {
            throw BusinessRuleViolation::make('refund_amount_exceeds', 'amount', ['max' => $available]);
        }

        $dual = $data->amountMinor >= $this->settings->int(SettingKey::RefundDualApprovalThresholdMinor);

        $refund = $this->refunds->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'payment_id' => $locked->id,
            'amount_minor' => $data->amountMinor,
            'currency' => $locked->currency,
            'status' => $dual ? RefundStatus::PendingApproval : RefundStatus::Approved,
            'reason' => $data->reason,
            'requires_dual_approval' => $dual,
            'requested_by_id' => $actor->id,
            'decided_by_id' => $dual ? null : $actor->id,
            'decided_at' => $dual ? null : CarbonImmutable::now(),
            'decision_reason' => $dual ? null : 'below_threshold',
            'idempotency_key' => $data->idempotencyKey,
        ]);

        $this->transitions->note($locked, PaymentEventType::RefundRequested, PaymentActor::admin($actor), ['refund' => $refund->uuid, 'amount_minor' => $refund->amount_minor, 'dual_approval' => $dual]);
        $this->audit->record(AuditAction::Requested, $refund, $actor, after: ['amount_minor' => $refund->amount_minor, 'status' => $refund->status->value, 'dual_approval' => $dual, 'payment' => $locked->uuid], reason: $data->reason);

        return $dual ? $refund : $this->execute($locked, $refund, PaymentActor::admin($actor));
    }

    /**
     * Sends an approved refund to the gateway. Payment locked by the caller.
     */
    private function execute(Payment $payment, PaymentRefund $refund, PaymentActor $actor): PaymentRefund
    {
        $gateway = $payment->gateway === null ? null : $this->gateways->forName($payment->gateway);

        if ($gateway === null || $payment->gateway_reference === null) {
            throw ServiceUnavailableException::make('payment_gateway_unavailable');
        }

        $currency = $this->currencies->get($refund->currency);

        try {
            $result = $gateway->refund(new RefundRequest(
                paymentReference: $payment->gateway_reference,
                refundReference: $refund->uuid,
                amount: Money::of($refund->amount_minor, $currency),
                reason: $refund->reason,
                transactionId: isset($payment->meta['gateway_transaction_id']) ? (string) $payment->meta['gateway_transaction_id'] : null,
            ));
        } catch (PaymentGatewayException) {
            return $this->fail($payment, $refund, 'gateway_error', $actor);
        }

        return match ($result->state) {
            GatewayRefundResult::SUCCEEDED => $this->complete($payment, $this->refunds->update($refund, ['gateway_reference' => $result->reference]), $actor),
            GatewayRefundResult::FAILED => $this->fail($payment, $this->refunds->update($refund, ['gateway_reference' => $result->reference]), $result->failureCode ?? 'refund_declined', $actor),
            default => $this->refunds->update($refund, ['status' => RefundStatus::Processing, 'gateway_reference' => $result->reference]),
        };
    }

    private function complete(Payment $payment, PaymentRefund $refund, PaymentActor $actor): PaymentRefund
    {
        if ($refund->status === RefundStatus::Completed) {
            return $refund;
        }

        $booking = $this->bookings->find($payment->booking_id);

        if (! $booking instanceof Booking || $payment->settlement_entry_id === null) {
            throw new LogicException('A refund needs the settlement entry of its payment.');
        }

        $entry = $this->entries->find($payment->settlement_entry_id) ?? throw new LogicException('Settlement entry missing.');
        $currency = $this->currencies->get($payment->currency);
        $original = $this->lines->forEntry($entry)->map(static fn (JournalLine $line): JournalLineData => new JournalLineData($line->ledger_account_id, $line->side, $line->amount ?? throw new LogicException('Line without amount.')))->values()->all();
        $gatewayAccount = $this->accounts->platform(LedgerAccountType::GatewayClearing, $payment->currency);

        $lines = $this->postings->reverse($original, $gatewayAccount->id, Money::of($payment->amount_minor, $currency), Money::of($refund->amount_minor, $currency));

        $result = $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::Refund,
            idempotencyKey: 'refund:'.$refund->uuid,
            descriptionKey: 'ledger.entry.refund',
            lines: $lines,
            referenceType: 'booking',
            referenceId: $booking->id,
            meta: ['booking' => $booking->code, 'payment' => $payment->uuid, 'refund' => $refund->uuid, 'reverses' => $entry->uuid, 'reason' => $refund->reason],
            actorType: $actor->type ?? ActorType::System,
            actorId: $actor->id,
            enforceNegativePolicy: false,
        ));

        $refunded = $payment->refunded_minor + $refund->amount_minor;
        $this->payments->update($payment, ['refunded_minor' => $refunded]);
        $target = $refunded >= $payment->amount_minor ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;
        $meta = ['refund' => $refund->uuid, 'amount_minor' => $refund->amount_minor, 'entry' => $result->entry->uuid];

        if ($payment->status === $target) {
            $this->transitions->note($payment, PaymentEventType::RefundCompleted, $actor, $meta);
        } else {
            $this->transitions->transition($payment, $target, PaymentEventType::RefundCompleted, $actor, meta: $meta);
        }

        // SRS §11.2 step 7: a fully refunded trip gives the coupon use and budget back when the coupon's terms say so (the ledger
        // reversal above already returned the discount funding); a partial refund keeps the discount.
        if ($target === PaymentStatus::Refunded && $payment->kind === PaymentKind::Trip) {
            $this->coupons->reverse($booking, 'trip_refunded');
        }

        return $this->refunds->update($refund, ['status' => RefundStatus::Completed, 'journal_entry_id' => $result->entry->id, 'completed_at' => CarbonImmutable::now(), 'failure_code' => null]);
    }

    private function fail(Payment $payment, PaymentRefund $refund, string $failureCode, PaymentActor $actor): PaymentRefund
    {
        $this->transitions->note($payment, PaymentEventType::RefundFailed, $actor, ['refund' => $refund->uuid, 'failure_code' => $failureCode]);

        return $this->refunds->update($refund, ['status' => RefundStatus::Failed, 'failure_code' => mb_substr($failureCode, 0, 64)]);
    }

    private function lockPayment(int $id): Payment
    {
        return $this->payments->lockById($id) ?? throw (new ModelNotFoundException)->setModel(Payment::class);
    }
}
