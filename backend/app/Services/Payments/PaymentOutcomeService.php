<?php

namespace App\Services\Payments;

use App\DTOs\Booking\TransitionContext;
use App\DTOs\Payments\PaymentActor;
use App\Enums\Booking\BookingStatus;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Booking\BookingTransitionService;
use Carbon\CarbonImmutable;

/**
 * Applies what the gateway said to a locked payment. Shared by the webhook processor and the status query, so a payment
 * ends in the same state whichever path learnt the answer first.
 *
 * `succeeded` is the only path to `paid` (Plan I8): it moves the payment, posts the ledger entry
 * (PaymentSettlementService), and for the trip payment asks BookingTransitionService - the only writer of
 * `bookings.status` - to move `completed_pending_payment` to `completed`. A booking in any other status (disputed,
 * already completed) is left alone; only its payment status follows.
 */
final class PaymentOutcomeService
{
    public function __construct(
        private readonly PaymentTransitionService $transitions,
        private readonly PaymentSettlementService $settlement,
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingTransitionService $bookingTransitions,
    ) {}

    public function succeeded(Payment $payment, PaymentActor $actor, ?CarbonImmutable $at = null, ?string $gatewayEventId = null): Payment
    {
        $type = $actor->source === 'query' ? PaymentEventType::QueryApplied : PaymentEventType::WebhookApplied;

        $this->transitions->transition($payment, PaymentStatus::Paid, $type, $actor, ['failure_code' => null], eventAt: $at ?? CarbonImmutable::now(), gatewayEventId: $gatewayEventId);
        $this->settlement->settle($payment);

        if ($payment->kind === PaymentKind::Trip) {
            $this->completeBooking($payment);
        }

        return $payment;
    }

    public function failed(Payment $payment, PaymentActor $actor, ?string $failureCode, ?CarbonImmutable $at = null, ?string $gatewayEventId = null): Payment
    {
        $type = $actor->source === 'query' ? PaymentEventType::QueryApplied : PaymentEventType::WebhookApplied;

        return $this->transitions->transition(
            $payment,
            PaymentStatus::Failed,
            $type,
            $actor,
            ['failure_code' => $failureCode !== null ? mb_substr($failureCode, 0, 64) : 'declined'],
            eventAt: $at ?? CarbonImmutable::now(),
            gatewayEventId: $gatewayEventId,
        );
    }

    public function requiresAction(Payment $payment, PaymentActor $actor, ?CarbonImmutable $at = null, ?string $gatewayEventId = null): Payment
    {
        $type = $actor->source === 'query' ? PaymentEventType::QueryApplied : PaymentEventType::WebhookApplied;

        return $this->transitions->transition($payment, PaymentStatus::RequiresAction, $type, $actor, eventAt: $at ?? CarbonImmutable::now(), gatewayEventId: $gatewayEventId);
    }

    /**
     * The payment cannot be trusted either way (timeout without an answer, amount mismatch): an operator decides.
     *
     * @param array<string, mixed> $meta
     */
    public function reconciliationRequired(Payment $payment, PaymentActor $actor, PaymentEventType $type, ?string $failureCode, array $meta = []): Payment
    {
        return $this->transitions->transition($payment, PaymentStatus::ReconciliationRequired, $type, $actor, ['failure_code' => $failureCode], $meta);
    }

    /**
     * completed_pending_payment -> completed, through BookingTransitionService (carry-over from P3.1).
     */
    private function completeBooking(Payment $payment): void
    {
        $booking = $this->bookings->lockById($payment->booking_id);

        if (! $booking instanceof Booking || $booking->status !== BookingStatus::CompletedPendingPayment) {
            return;
        }

        $this->bookingTransitions->transition($booking, BookingStatus::Completed, TransitionContext::system('payment_confirmed', ['payment' => $payment->uuid]));
    }
}
