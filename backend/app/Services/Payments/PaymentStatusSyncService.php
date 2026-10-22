<?php

namespace App\Services\Payments;

use App\DTOs\Payments\PaymentActor;
use App\Enums\Common\AuditAction;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Settings\SettingKey;
use App\Integrations\Payments\Contracts\PaymentGateway;
use App\Integrations\Payments\Data\GatewayPaymentState;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Learns a payment's state by asking the gateway (Plan I8: "verified webhook OR query"). Used when the customer returns
 * from the payment page (the app asks the platform to check, it never reports success itself), and by the timeout sweep
 * that handles "customer closed the app" and lost webhooks (SRS §12.2).
 */
final class PaymentStatusSyncService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentOutcomeService $outcomes,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * The customer-triggered check of one of their own payments. Returns the payment as it is after the check.
     *
     * @throws ModelNotFoundException another customer's payment is a 404
     * @throws PaymentGatewayException the gateway could not be asked
     */
    public function refresh(User $customer, string $paymentUuid): Payment
    {
        $payment = $this->payments->findByUuid($paymentUuid);

        if (! $payment instanceof Payment || $payment->user_id !== $customer->id) {
            throw (new ModelNotFoundException)->setModel(Payment::class);
        }

        if ($payment->gateway === null || $payment->gateway_reference === null) {
            return $payment;
        }

        $gateway = $this->gateways->forName($payment->gateway);

        if ($gateway === null) {
            return $payment;
        }

        return $this->db->transaction(function () use ($payment, $gateway): Payment {
            $locked = $this->payments->lockById($payment->id) ?? throw (new ModelNotFoundException)->setModel(Payment::class);

            if (in_array($locked->status, [...PaymentStatus::awaitingGateway(), PaymentStatus::ReconciliationRequired, PaymentStatus::Failed], true)) {
                $this->apply($locked, $gateway, PaymentActor::query(), timeout: false);
            }

            return $locked;
        });
    }

    /**
     * An operator asks the gateway about a payment (reconciliation tool). Same verified-query path as the customer refresh,
     * with the operator and the mandatory reason on the timeline and in the audit log.
     *
     * @throws PaymentGatewayException the gateway could not be asked
     */
    public function refreshForAdmin(Payment $payment, Admin $admin, string $reason): Payment
    {
        $gateway = $payment->gateway === null ? null : $this->gateways->forName($payment->gateway);

        if ($gateway === null || $payment->gateway_reference === null) {
            throw BusinessRuleViolation::make('payments_not_configured');
        }

        return $this->db->transaction(function () use ($payment, $gateway, $admin, $reason): Payment {
            $locked = $this->payments->lockById($payment->id) ?? throw (new ModelNotFoundException)->setModel(Payment::class);
            $before = $locked->status;

            $this->apply($locked, $gateway, PaymentActor::admin($admin), timeout: false);
            $this->audit->record(AuditAction::Reconciled, $locked, $admin, before: ['status' => $before->value], after: ['status' => $locked->status->value], reason: $reason);

            return $locked;
        });
    }

    /**
     * Payments that waited for the gateway longer than the timeout: ask it once; paid / failed are applied, anything still
     * open is flagged `reconciliation_required`. Returns how many were flagged.
     */
    public function sweepTimeouts(int $limit = 100): int
    {
        $cutoff = CarbonImmutable::now()->subMinutes($this->settings->int(SettingKey::PaymentIntentTimeoutMinutes));
        $flagged = 0;

        foreach ($this->payments->awaitingGatewayCreatedBefore($cutoff, $limit) as $payment) {
            $gateway = $payment->gateway === null ? null : $this->gateways->forName($payment->gateway);

            if ($gateway === null) {
                continue;
            }

            try {
                $flagged += $this->db->transaction(function () use ($payment, $gateway): int {
                    $locked = $this->payments->lockById($payment->id);

                    if (! $locked instanceof Payment || ! in_array($locked->status, PaymentStatus::awaitingGateway(), true)) {
                        return 0;
                    }

                    return $this->apply($locked, $gateway, PaymentActor::system(), timeout: true) ? 1 : 0;
                });
            } catch (PaymentGatewayException) {
                // The gateway is down: leave the payment, the next run asks again.
                continue;
            }
        }

        return $flagged;
    }

    /**
     * @return bool true when the payment ended up flagged `reconciliation_required`
     *
     * @throws PaymentGatewayException
     */
    private function apply(Payment $payment, PaymentGateway $gateway, PaymentActor $actor, bool $timeout): bool
    {
        $status = $gateway->queryStatus((string) $payment->gateway_reference);

        if ($status->state === GatewayPaymentState::Succeeded) {
            if (($status->amountMinor !== null && $status->amountMinor !== $payment->amount_minor) || ($status->currency !== null && strtoupper($status->currency) !== $payment->currency)) {
                $this->outcomes->reconciliationRequired($payment, $actor, PaymentEventType::AmountMismatch, 'amount_mismatch', ['received_minor' => $status->amountMinor]);

                return true;
            }

            $this->outcomes->succeeded($payment, $actor);

            return false;
        }

        if ($status->state === GatewayPaymentState::Failed) {
            if (in_array($payment->status, [...PaymentStatus::awaitingGateway(), PaymentStatus::ReconciliationRequired], true)) {
                $this->outcomes->failed($payment, $actor, $status->failureCode);
            }

            return false;
        }

        if ($status->state === GatewayPaymentState::RequiresAction && $payment->status === PaymentStatus::Pending) {
            $this->outcomes->requiresAction($payment, $actor);
        }

        if ($timeout && in_array($payment->status, PaymentStatus::awaitingGateway(), true)) {
            $this->outcomes->reconciliationRequired(
                $payment,
                $actor,
                PaymentEventType::TimedOut,
                $status->state === GatewayPaymentState::NotFound ? 'not_found_at_gateway' : 'timeout',
            );

            return true;
        }

        return false;
    }
}
