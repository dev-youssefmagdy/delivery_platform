<?php

namespace App\Services\Payments;

use App\DTOs\Payments\PaymentActor;
use App\DTOs\Payments\WebhookProcessingResult;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\WebhookEventKind;
use App\Enums\Payments\WebhookOutcome;
use App\Integrations\Payments\Contracts\PaymentGateway;
use App\Integrations\Payments\Data\GatewayPaymentState;
use App\Integrations\Payments\Data\VerifiedWebhook;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;

/**
 * Applies one verified, de-duplicated webhook to the platform. Runs inside the caller's transaction.
 *
 * Out-of-order, late and duplicate events (SRS §12.2), by rule:
 * - `succeeded` wins over everything that is not already collected: a late success after `failed` or a timeout is real
 *   money and moves the payment to `paid`. A success for a payment that is already paid / refunded is ignored.
 * - `failed` and `requires_action` only move a payment that still awaits the gateway, and only if the event is not older
 *   than the last event applied (a stale "failed" after "paid" is ignored).
 * - A callback whose amount or currency differs from the payment is never applied: the payment goes to
 *   `reconciliation_required` and the event waits for an operator (`amount_mismatch`).
 * - When the gateway asks for it (`confirmsWebhooksByQuery`), a success is confirmed by querying the gateway first.
 */
final class PaymentWebhookProcessor
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOutcomeService $outcomes,
        private readonly PaymentTransitionService $transitions,
        private readonly PaymentMethodService $methods,
        private readonly RefundService $refunds,
    ) {}

    public function apply(PaymentGatewayName $name, PaymentGateway $gateway, VerifiedWebhook $event): WebhookProcessingResult
    {
        if ($event->kind === WebhookEventKind::MethodSaved) {
            return $this->methods->activateFromWebhook($name, $event)
                ? new WebhookProcessingResult(WebhookOutcome::Processed)
                : new WebhookProcessingResult(WebhookOutcome::Unmatched, note: 'method_owner_not_found');
        }

        if ($event->kind === WebhookEventKind::Unknown || $event->gatewayReference === null) {
            return new WebhookProcessingResult(WebhookOutcome::Ignored, note: 'not_relevant');
        }

        $payment = $this->payments->lockByGatewayReference($name, $event->gatewayReference);

        if (! $payment instanceof Payment) {
            return new WebhookProcessingResult(WebhookOutcome::Unmatched, note: 'payment_not_found');
        }

        if ($event->kind->isRefund()) {
            return $this->applyRefund($payment, $event);
        }

        if ($this->amountMismatches($payment, $event)) {
            $this->transitions->note($payment, PaymentEventType::AmountMismatch, PaymentActor::webhook(), [
                'expected_minor' => $payment->amount_minor,
                'received_minor' => $event->amountMinor,
                'currency' => $event->currency,
            ], $event->eventId);

            if (in_array($payment->status, PaymentStatus::awaitingGateway(), true)) {
                $this->outcomes->reconciliationRequired($payment, PaymentActor::webhook(), PaymentEventType::AmountMismatch, 'amount_mismatch');
            }

            return new WebhookProcessingResult(WebhookOutcome::AmountMismatch, $payment, 'amount_mismatch');
        }

        return match ($event->kind) {
            WebhookEventKind::PaymentSucceeded => $this->applySucceeded($payment, $gateway, $event),
            WebhookEventKind::PaymentFailed => $this->applyFailed($payment, $event),
            default => $this->applyRequiresAction($payment, $event),
        };
    }

    private function applySucceeded(Payment $payment, PaymentGateway $gateway, VerifiedWebhook $event): WebhookProcessingResult
    {
        if ($payment->status->isCollected() || $payment->status === PaymentStatus::CashDue) {
            return $this->ignored($payment, $event, 'already_collected');
        }

        if ($gateway->confirmsWebhooksByQuery()) {
            try {
                $status = $gateway->queryStatus((string) $payment->gateway_reference);
            } catch (PaymentGatewayException) {
                return new WebhookProcessingResult(WebhookOutcome::Failed, $payment, 'gateway_query_failed');
            }

            if ($status->state !== GatewayPaymentState::Succeeded || ($status->amountMinor !== null && $status->amountMinor !== $payment->amount_minor)) {
                return new WebhookProcessingResult(WebhookOutcome::Failed, $payment, 'gateway_query_disagrees');
            }
        }

        $this->outcomes->succeeded($payment, PaymentActor::webhook(), $event->occurredAt, $event->eventId);

        return new WebhookProcessingResult(WebhookOutcome::Processed, $payment);
    }

    private function applyFailed(Payment $payment, VerifiedWebhook $event): WebhookProcessingResult
    {
        if (! $this->canMoveByEvent($payment, $event, [PaymentStatus::Pending, PaymentStatus::RequiresAction, PaymentStatus::Authorized, PaymentStatus::ReconciliationRequired])) {
            return $this->ignored($payment, $event, 'stale_or_final');
        }

        $this->outcomes->failed($payment, PaymentActor::webhook(), $event->failureCode, $event->occurredAt, $event->eventId);

        return new WebhookProcessingResult(WebhookOutcome::Processed, $payment);
    }

    private function applyRequiresAction(Payment $payment, VerifiedWebhook $event): WebhookProcessingResult
    {
        if (! $this->canMoveByEvent($payment, $event, [PaymentStatus::Pending])) {
            return $this->ignored($payment, $event, 'stale_or_final');
        }

        $this->outcomes->requiresAction($payment, PaymentActor::webhook(), $event->occurredAt, $event->eventId);

        return new WebhookProcessingResult(WebhookOutcome::Processed, $payment);
    }

    private function applyRefund(Payment $payment, VerifiedWebhook $event): WebhookProcessingResult
    {
        $refund = $this->refunds->applyGatewayResult(
            $payment,
            $event->refundReference,
            $event->kind === WebhookEventKind::RefundSucceeded,
            $event->failureCode,
            PaymentActor::webhook(),
        );

        return $refund === null
            ? new WebhookProcessingResult(WebhookOutcome::Unmatched, $payment, 'refund_not_found')
            : new WebhookProcessingResult(WebhookOutcome::Processed, $payment);
    }

    /**
     * @param list<PaymentStatus> $from
     */
    private function canMoveByEvent(Payment $payment, VerifiedWebhook $event, array $from): bool
    {
        if (! in_array($payment->status, $from, true)) {
            return false;
        }

        return $payment->last_event_at === null || ! $event->occurredAt->lessThan($payment->last_event_at);
    }

    private function amountMismatches(Payment $payment, VerifiedWebhook $event): bool
    {
        if ($event->amountMinor === null && $event->currency === null) {
            return false;
        }

        return ($event->amountMinor !== null && $event->amountMinor !== $payment->amount_minor)
            || ($event->currency !== null && strtoupper($event->currency) !== $payment->currency);
    }

    private function ignored(Payment $payment, VerifiedWebhook $event, string $reason): WebhookProcessingResult
    {
        $this->transitions->note($payment, PaymentEventType::WebhookIgnored, PaymentActor::webhook(), ['reason' => $reason, 'kind' => $event->kind->value], $event->eventId);

        return new WebhookProcessingResult(WebhookOutcome::Ignored, $payment, $reason);
    }
}
