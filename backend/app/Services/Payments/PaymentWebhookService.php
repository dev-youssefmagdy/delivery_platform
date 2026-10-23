<?php

namespace App\Services\Payments;

use App\DTOs\Payments\WebhookProcessingResult;
use App\DTOs\Payments\WebhookReceipt;
use App\Enums\Common\AuditAction;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\WebhookEventKind;
use App\Enums\Payments\WebhookOutcome;
use App\Exceptions\BusinessRuleViolation;
use App\Integrations\Payments\Contracts\PaymentGateway;
use App\Integrations\Payments\Data\VerifiedWebhook;
use App\Integrations\Payments\Data\WebhookRequest;
use App\Integrations\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Integrations\Payments\Exceptions\WebhookReplayException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Admin;
use App\Models\GatewayWebhookEvent;
use App\Repositories\Contracts\Payments\GatewayWebhookEventRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * The entry point of every payment gateway callback (Plan I8, SRS §12.2, §20.7):
 *
 * 1. the driver verifies the signature (constant time) and, where the gateway signs a timestamp, the replay window;
 *    anything else is recorded as `invalid_signature` / `replay_window` and answered 401 / 400 without touching a payment;
 * 2. the event is recorded under a unique (gateway, event_id): a second delivery of a processed event is a `duplicate`
 *    and changes nothing (the row lock makes two simultaneous deliveries wait for each other);
 * 3. the processor applies it in one transaction together with the money it moves; if that fails the event is kept as
 *    `failed` and the gateway is told to retry (HTTP 500).
 *
 * Nothing here reads the Flutter apps: the apps' "payment completed" callback cannot reach this code (SRS §12.2).
 */
final class PaymentWebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly GatewayWebhookEventRepositoryInterface $events,
        private readonly PaymentWebhookProcessor $processor,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function receive(PaymentGatewayName $name, WebhookRequest $request): WebhookReceipt
    {
        $gateway = $this->gateways->forName($name) ?? throw (new ModelNotFoundException)->setModel(GatewayWebhookEvent::class);
        $now = CarbonImmutable::now();
        $hash = hash('sha256', $request->body);

        try {
            $verified = $gateway->verifyWebhook($request, $now);
        } catch (InvalidWebhookSignatureException) {
            return $this->reject($name, $hash, false, WebhookOutcome::InvalidSignature, $now);
        } catch (WebhookReplayException) {
            return $this->reject($name, $hash, true, WebhookOutcome::ReplayWindow, $now);
        }

        try {
            return $this->db->transaction(fn (): WebhookReceipt => $this->handle($name, $gateway, $verified, $hash, $now));
        } catch (Throwable $exception) {
            return $this->recordFailure($name, $verified, $hash, $now, $exception);
        }
    }

    /**
     * Runs a failed / unmatched / mismatching event through the processor again (an operator, with a reason; audited).
     *
     * @throws BusinessRuleViolation the event cannot be processed again
     */
    public function reprocess(GatewayWebhookEvent $event, Admin $actor, string $reason): WebhookReceipt
    {
        $gateway = $this->gateways->forName($event->gateway) ?? throw BusinessRuleViolation::make('payments_not_configured');

        try {
            $receipt = $this->db->transaction(function () use ($event, $actor, $reason, $gateway): WebhookReceipt {
                $locked = $this->events->lockById($event->id) ?? throw (new ModelNotFoundException)->setModel(GatewayWebhookEvent::class);

                if (! $locked->signature_valid || $locked->processed_at !== null || ! $locked->outcome->canReprocess()) {
                    throw BusinessRuleViolation::make('webhook_cannot_reprocess');
                }

                $verified = $this->restore($locked);
                $result = $this->processor->apply($locked->gateway, $gateway, $verified);
                $this->events->update($locked, $this->resultAttributes($result) + [
                    'attempts' => $locked->attempts + 1,
                    'reprocessed_by_id' => $actor->id,
                    'reprocess_reason' => $reason,
                ]);
                $this->audit->record(AuditAction::Reprocessed, $locked, $actor, after: ['outcome' => $result->outcome->value], reason: $reason);

                return new WebhookReceipt($result->outcome, $locked->uuid);
            });
        } catch (BusinessRuleViolation|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->events->update($event, ['error' => Str::limit($exception::class, 120, ''), 'attempts' => $event->attempts + 1, 'outcome' => WebhookOutcome::Failed]);
            Log::error('payments.webhook.reprocess_failed', ['event' => $event->uuid, 'exception' => $exception::class]);

            throw BusinessRuleViolation::make('webhook_reprocess_failed');
        }

        return $receipt;
    }

    private function handle(PaymentGatewayName $name, PaymentGateway $gateway, VerifiedWebhook $verified, string $hash, CarbonImmutable $now): WebhookReceipt
    {
        $event = $this->events->lockByEventId($name, $verified->eventId);

        if (! $event instanceof GatewayWebhookEvent) {
            try {
                $event = $this->events->insert($this->newEventAttributes($name, $verified, $hash, $now));
            } catch (UniqueConstraintViolationException) {
                // A concurrent delivery of the same event inserted first: wait for its lock and read what it did.
                $event = $this->events->lockByEventId($name, $verified->eventId) ?? throw new LogicException('Webhook event vanished.');
            }
        }

        if ($event->processed_at !== null) {
            $this->events->update($event, ['attempts' => $event->attempts + 1]);

            return new WebhookReceipt(WebhookOutcome::Duplicate, $event->uuid);
        }

        $result = $this->processor->apply($name, $gateway, $verified);
        $this->events->update($event, $this->resultAttributes($result) + ['attempts' => $event->attempts + ($event->outcome === WebhookOutcome::Received ? 0 : 1)]);

        return new WebhookReceipt($result->outcome, $event->uuid);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultAttributes(WebhookProcessingResult $result): array
    {
        return [
            'outcome' => $result->outcome,
            'payment_id' => $result->payment?->id,
            'processed_at' => $result->isFinal() ? CarbonImmutable::now() : null,
            'error' => $result->note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newEventAttributes(PaymentGatewayName $name, VerifiedWebhook $verified, string $hash, CarbonImmutable $now): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'gateway' => $name,
            'event_id' => mb_substr($verified->eventId, 0, 160),
            'kind' => $verified->kind,
            'signature_valid' => true,
            'payload_hash' => $hash,
            'payload' => $this->snapshot($verified),
            'gateway_reference' => $verified->gatewayReference !== null ? mb_substr($verified->gatewayReference, 0, 120) : null,
            'occurred_at' => $verified->occurredAt,
            'received_at' => $now,
            'outcome' => WebhookOutcome::Received,
            'attempts' => 1,
        ];
    }

    private function reject(PaymentGatewayName $name, string $hash, bool $signatureValid, WebhookOutcome $outcome, CarbonImmutable $now): WebhookReceipt
    {
        $event = $this->events->insert([
            'uuid' => (string) Str::uuid(),
            'gateway' => $name,
            'event_id' => 'rejected:'.Str::uuid(),
            'signature_valid' => $signatureValid,
            'payload_hash' => $hash,
            'received_at' => $now,
            'processed_at' => $now,
            'outcome' => $outcome,
            'attempts' => 1,
        ]);

        Log::warning('payments.webhook.rejected', ['gateway' => $name->value, 'outcome' => $outcome->value]);

        return new WebhookReceipt($outcome, $event->uuid);
    }

    private function recordFailure(PaymentGatewayName $name, VerifiedWebhook $verified, string $hash, CarbonImmutable $now, Throwable $exception): WebhookReceipt
    {
        Log::error('payments.webhook.failed', ['gateway' => $name->value, 'event' => $verified->eventId, 'exception' => $exception::class, 'message' => Str::limit($exception->getMessage(), 200, '')]);

        $existing = $this->events->findByEventId($name, $verified->eventId);
        $attributes = ['outcome' => WebhookOutcome::Failed, 'error' => Str::limit($exception::class, 120, ''), 'processed_at' => null];

        if ($existing instanceof GatewayWebhookEvent) {
            $this->events->update($existing, $attributes + ['attempts' => $existing->attempts + 1]);
            $uuid = $existing->uuid;
        } else {
            $uuid = $this->events->insert($this->newEventAttributes($name, $verified, $hash, $now) + $attributes)->uuid;
        }

        return new WebhookReceipt(WebhookOutcome::Failed, $uuid);
    }

    /**
     * The neutral fields kept with the event so it can be processed again later. The saved-method token is encrypted.
     *
     * @return array<string, mixed>
     */
    private function snapshot(VerifiedWebhook $verified): array
    {
        return [
            'neutral' => [
                'kind' => $verified->kind->value,
                'reference' => $verified->gatewayReference,
                'amount_minor' => $verified->amountMinor,
                'currency' => $verified->currency,
                'failure_code' => $verified->failureCode,
                'refund_reference' => $verified->refundReference,
                'brand' => $verified->methodBrand,
                'last4' => $verified->methodLast4,
                'setup_reference' => $verified->setupReference,
                'token' => $verified->methodToken === null ? null : Crypt::encryptString($verified->methodToken),
            ],
            'raw' => $verified->payload,
        ];
    }

    private function restore(GatewayWebhookEvent $event): VerifiedWebhook
    {
        /** @var array<string, mixed> $neutral */
        $neutral = (array) ($event->payload['neutral'] ?? []);

        return new VerifiedWebhook(
            eventId: $event->event_id,
            kind: WebhookEventKind::tryFrom((string) ($neutral['kind'] ?? '')) ?? WebhookEventKind::Unknown,
            occurredAt: $event->occurred_at !== null ? CarbonImmutable::instance($event->occurred_at) : CarbonImmutable::now(),
            gatewayReference: isset($neutral['reference']) ? (string) $neutral['reference'] : null,
            amountMinor: isset($neutral['amount_minor']) ? (int) $neutral['amount_minor'] : null,
            currency: isset($neutral['currency']) ? (string) $neutral['currency'] : null,
            failureCode: isset($neutral['failure_code']) ? (string) $neutral['failure_code'] : null,
            refundReference: isset($neutral['refund_reference']) ? (string) $neutral['refund_reference'] : null,
            methodToken: isset($neutral['token']) ? Crypt::decryptString((string) $neutral['token']) : null,
            methodBrand: isset($neutral['brand']) ? (string) $neutral['brand'] : null,
            methodLast4: isset($neutral['last4']) ? (string) $neutral['last4'] : null,
            setupReference: isset($neutral['setup_reference']) ? (string) $neutral['setup_reference'] : null,
            payload: (array) ($event->payload['raw'] ?? []),
        );
    }
}
