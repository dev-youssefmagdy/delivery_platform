<?php

namespace App\Services\Payments;

use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\ReconciliationStatus;
use App\Enums\Payments\PaymentStatus;
use App\Integrations\Payments\Data\GatewayPaymentState;
use App\Integrations\Payments\Data\GatewayTransaction;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\PaymentReconciliationRun;
use App\Repositories\Contracts\Payments\PaymentReconciliationRunRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * `payments:reconcile` (SRS §12.2 "reconciliation reports for transactions with mismatched local/provider status").
 *
 * 1. Sweeps payments that waited for the gateway past the timeout (PaymentStatusSyncService): paid / failed are applied from
 *    the gateway's answer, the rest becomes `reconciliation_required`.
 * 2. Compares the platform's online payments of the look-back window with the gateway's transactions and REPORTS every
 *    difference. The report is stored, logged at critical level and shown in the admin; the comparison itself changes
 *    nothing (a human or the explicit refresh / reprocess actions fix things, with an audit trail).
 */
final class PaymentReconciliationService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentReconciliationRunRepositoryInterface $runs,
        private readonly PaymentStatusSyncService $sync,
        private readonly AuditLogger $audit,
    ) {}

    public function run(?Admin $actor = null, bool $sweep = true): PaymentReconciliationRun
    {
        $timedOut = $sweep ? $this->sync->sweepTimeouts() : 0;
        $now = CarbonImmutable::now();
        $from = $now->subHours((int) config('payments.reconcile.window_hours', 48));
        $to = $now->subMinutes((int) config('payments.reconcile.grace_minutes', 10));

        [$findings, $checked, $transactions] = $this->compare($from, $to);

        $run = $this->runs->create([
            'status' => $findings === [] ? ReconciliationStatus::Ok : ReconciliationStatus::Mismatch,
            'window_from' => $from,
            'window_to' => $to,
            'payments_checked' => $checked,
            'transactions_checked' => $transactions,
            'timed_out' => $timedOut,
            'findings' => $findings === [] ? null : $findings,
            'triggered_by_type' => $actor instanceof Admin ? ActorType::Admin->value : ActorType::System->value,
            'triggered_by_id' => $actor?->id,
            'ran_at' => $now,
        ]);

        if ($findings !== []) {
            Log::critical('payments.reconcile.mismatch', ['run' => $run->uuid, 'findings' => count($findings), 'first' => $findings[0]]);
        }

        if ($actor instanceof Admin) {
            $this->audit->record(AuditAction::Reconciled, $run, $actor, after: ['status' => $run->status->value, 'findings' => count($findings)]);
        }

        return $run;
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int}
     */
    private function compare(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $name = $this->gateways->configuredName();
        $gateway = $name === null ? null : $this->gateways->forName($name);

        if ($gateway === null) {
            return [[], 0, 0];
        }

        $local = $this->payments->onlineInWindow($from, $to)->filter(static fn (Payment $payment): bool => $payment->gateway === $name)->values();

        try {
            $remote = $gateway->listTransactions($from, $to, $local->pluck('gateway_reference')->filter()->values()->all());
        } catch (PaymentGatewayException) {
            return [[['kind' => 'gateway_unreachable']], $local->count(), 0];
        }

        /** @var array<string, GatewayTransaction> $byReference */
        $byReference = [];

        foreach ($remote as $transaction) {
            $byReference[$transaction->reference] = $transaction;
        }

        $findings = [];

        foreach ($local as $payment) {
            $transaction = $byReference[(string) $payment->gateway_reference] ?? null;

            foreach ($this->differences($payment, $transaction) as $finding) {
                $findings[] = $finding + ['payment_uuid' => $payment->uuid, 'gateway_reference' => $payment->gateway_reference, 'local_status' => $payment->status->value];
            }
        }

        $known = $local->pluck('gateway_reference')->filter()->flip();

        foreach ($remote as $transaction) {
            if (! $known->has($transaction->reference)) {
                $findings[] = ['kind' => 'unknown_transaction', 'gateway_reference' => $transaction->reference, 'remote_state' => $transaction->state->value, 'amount_minor' => $transaction->amountMinor];
            }
        }

        return [$findings, $local->count(), count($remote)];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function differences(Payment $payment, ?GatewayTransaction $remote): array
    {
        $collected = $payment->status->isCollected();

        if (! $remote instanceof GatewayTransaction) {
            return $collected ? [['kind' => 'missing_at_gateway']] : [];
        }

        $findings = [];
        $remoteSucceeded = $remote->state === GatewayPaymentState::Succeeded;

        if ($collected && ! $remoteSucceeded) {
            $findings[] = ['kind' => 'paid_locally_not_at_gateway', 'remote_state' => $remote->state->value];
        } elseif (! $collected && $remoteSucceeded && $payment->status !== PaymentStatus::CashDue) {
            $findings[] = ['kind' => 'paid_at_gateway_not_locally', 'remote_state' => $remote->state->value];
        }

        if ($remoteSucceeded && ($remote->amountMinor !== $payment->amount_minor || $remote->currency !== $payment->currency)) {
            $findings[] = ['kind' => 'amount_mismatch', 'local_minor' => $payment->amount_minor, 'remote_minor' => $remote->amountMinor];
        }

        if ($remoteSucceeded && $remote->refundedMinor !== $payment->refunded_minor) {
            $findings[] = ['kind' => 'refund_mismatch', 'local_minor' => $payment->refunded_minor, 'remote_minor' => $remote->refundedMinor];
        }

        return $findings;
    }
}
