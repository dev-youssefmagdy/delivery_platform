<?php

namespace App\Integrations\Payments\Drivers;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\WebhookEventKind;
use App\Integrations\Payments\Contracts\PaymentGateway;
use App\Integrations\Payments\Data\GatewayIntent;
use App\Integrations\Payments\Data\GatewayPaymentState;
use App\Integrations\Payments\Data\GatewayRefundResult;
use App\Integrations\Payments\Data\GatewayStatus;
use App\Integrations\Payments\Data\GatewayTransaction;
use App\Integrations\Payments\Data\PaymentIntentRequest;
use App\Integrations\Payments\Data\RefundRequest;
use App\Integrations\Payments\Data\SetupIntentRequest;
use App\Integrations\Payments\Data\VerifiedWebhook;
use App\Integrations\Payments\Data\WebhookRequest;
use App\Integrations\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\Exceptions\UnsupportedPaymentOperationException;
use App\Integrations\Payments\Exceptions\WebhookReplayException;
use App\Jobs\Payments\DeliverSandboxWebhook;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

/**
 * Local/testing processor (refused at boot elsewhere, ADR-12). It behaves like a real asynchronous gateway:
 *
 * - `createIntent()` returns `pending` and queues a job that, a moment later, delivers a SIGNED webhook (HMAC-SHA256 over
 *   `<timestamp>.<body>`, the same shape real processors use) to the same entry point as a real callback. Nothing is ever
 *   marked paid synchronously, and the platform verifies the signature and the replay window like for any gateway.
 * - Its "bank side" is a small record in the cache so `queryStatus()` and `listTransactions()` answer truthfully.
 * - `PAYMENT_SANDBOX_OUTCOME` chooses what happens: `succeed` (default), `fail`, `requires_action` (then succeeds) or
 *   `silent` (no webhook at all, to exercise timeouts and reconciliation).
 */
final class SandboxPaymentGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'x-sandbox-signature';

    private const TX_KEY = 'sandbox-payments:tx:';

    private const INDEX_KEY = 'sandbox-payments:index';

    public function __construct(private readonly Cache $cache) {}

    public function name(): PaymentGatewayName
    {
        return PaymentGatewayName::Sandbox;
    }

    public function createIntent(PaymentIntentRequest $request): GatewayIntent
    {
        $reference = 'sbx_pay_'.Str::lower(Str::random(20));

        $this->write($reference, [
            'state' => GatewayPaymentState::Pending->value,
            'amount_minor' => $request->amount->amount,
            'currency' => $request->amount->currency->code,
            'refunded_minor' => 0,
            'created_at' => CarbonImmutable::now()->getTimestamp(),
        ]);
        $this->remember($reference);

        $outcome = (string) config('payments.sandbox.outcome', 'succeed');

        if ($outcome === 'requires_action') {
            $this->deliver('payment.requires_action', ['reference' => $reference, 'amount_minor' => $request->amount->amount, 'currency' => $request->amount->currency->code], 0);
        }

        if ($outcome !== 'silent') {
            $this->deliver($outcome === 'fail' ? 'payment.failed' : 'payment.succeeded', [
                'reference' => $reference,
                'amount_minor' => $request->amount->amount,
                'currency' => $request->amount->currency->code,
                'failure_code' => $outcome === 'fail' ? 'card_declined' : null,
            ], $outcome === 'requires_action' ? 2 : 0);
        }

        return new GatewayIntent(
            reference: $reference,
            status: $outcome === 'requires_action' ? PaymentStatus::RequiresAction : PaymentStatus::Pending,
            clientSecret: $reference.'_secret',
            actionUrl: 'https://sandbox.invalid/pay/'.$reference,
            expiresAt: CarbonImmutable::now()->addMinutes(30),
        );
    }

    public function createSetupIntent(SetupIntentRequest $request): GatewayIntent
    {
        $reference = 'sbx_setup_'.Str::lower(Str::random(20));

        $this->deliver('method.saved', [
            'reference' => $reference,
            'setup_reference' => $reference,
            'token' => 'sbx_tok_'.Str::lower(Str::random(24)),
            'brand' => 'visa',
            'last4' => '4242',
        ], 0);

        return new GatewayIntent(reference: $reference, clientSecret: $reference.'_secret', actionUrl: 'https://sandbox.invalid/setup/'.$reference);
    }

    public function supportsAuthorization(): bool
    {
        return false;
    }

    public function capture(string $gatewayReference, Money $amount): void
    {
        throw new UnsupportedPaymentOperationException('The sandbox gateway has no separate capture.');
    }

    public function verifyWebhook(WebhookRequest $request, CarbonImmutable $now): VerifiedWebhook
    {
        $header = $request->header(self::SIGNATURE_HEADER);

        if ($header === null || preg_match('/^t=(\d{9,12}),v1=([a-f0-9]{64})$/', $header, $parts) !== 1) {
            throw new InvalidWebhookSignatureException('Missing or malformed signature.');
        }

        $expected = hash_hmac('sha256', $parts[1].'.'.$request->body, $this->secret());

        if (! hash_equals($expected, $parts[2])) {
            throw new InvalidWebhookSignatureException('Signature mismatch.');
        }

        // Signed timestamp: a captured request cannot be replayed after the window.
        if (abs($now->getTimestamp() - (int) $parts[1]) > (int) config('payments.webhook.tolerance_seconds', 300)) {
            throw new WebhookReplayException('Timestamp outside the replay window.');
        }

        /** @var array<string, mixed>|null $event */
        $event = json_decode($request->body, true);

        if (! is_array($event) || ! isset($event['id'], $event['type'])) {
            throw new InvalidWebhookSignatureException('Malformed event.');
        }

        $kind = match ((string) $event['type']) {
            'payment.succeeded' => WebhookEventKind::PaymentSucceeded,
            'payment.failed' => WebhookEventKind::PaymentFailed,
            'payment.requires_action' => WebhookEventKind::PaymentRequiresAction,
            'refund.succeeded' => WebhookEventKind::RefundSucceeded,
            'refund.failed' => WebhookEventKind::RefundFailed,
            'method.saved' => WebhookEventKind::MethodSaved,
            default => WebhookEventKind::Unknown,
        };

        $data = (array) ($event['data'] ?? []);
        $token = isset($data['token']) ? (string) $data['token'] : null;
        unset($data['token']);

        return new VerifiedWebhook(
            eventId: (string) $event['id'],
            kind: $kind,
            occurredAt: CarbonImmutable::createFromTimestamp((int) ($event['created'] ?? $parts[1])),
            gatewayReference: isset($data['reference']) ? (string) $data['reference'] : null,
            amountMinor: isset($data['amount_minor']) ? (int) $data['amount_minor'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            failureCode: isset($data['failure_code']) ? (string) $data['failure_code'] : null,
            refundReference: isset($data['refund_reference']) ? (string) $data['refund_reference'] : null,
            methodToken: $token,
            methodBrand: isset($data['brand']) ? (string) $data['brand'] : null,
            methodLast4: isset($data['last4']) ? (string) $data['last4'] : null,
            setupReference: isset($data['setup_reference']) ? (string) $data['setup_reference'] : null,
            payload: ['id' => $event['id'], 'type' => $event['type'], 'created' => $event['created'] ?? null, 'data' => $data],
        );
    }

    public function confirmsWebhooksByQuery(): bool
    {
        return false;
    }

    public function queryStatus(string $gatewayReference): GatewayStatus
    {
        $tx = $this->read($gatewayReference);

        if ($tx === null) {
            return new GatewayStatus(GatewayPaymentState::NotFound);
        }

        return new GatewayStatus(
            GatewayPaymentState::from((string) $tx['state']),
            (int) $tx['amount_minor'],
            (string) $tx['currency'],
            (int) $tx['refunded_minor'],
            $tx['failure_code'] ?? null,
        );
    }

    public function refund(RefundRequest $request): GatewayRefundResult
    {
        $tx = $this->read($request->paymentReference) ?? throw new PaymentGatewayException('Unknown payment.');

        if ($tx['state'] !== GatewayPaymentState::Succeeded->value) {
            throw new PaymentGatewayException('Only a succeeded payment can be refunded.');
        }

        $reference = 'sbx_ref_'.Str::lower(Str::random(20));
        $outcome = (string) config('payments.sandbox.refund_outcome', 'succeed');

        if ($outcome !== 'silent') {
            $this->deliver($outcome === 'fail' ? 'refund.failed' : 'refund.succeeded', [
                'reference' => $request->paymentReference,
                'refund_reference' => $reference,
                'amount_minor' => $request->amount->amount,
                'currency' => $request->amount->currency->code,
                'failure_code' => $outcome === 'fail' ? 'refund_declined' : null,
            ], 0);
        }

        return new GatewayRefundResult($reference, GatewayRefundResult::PROCESSING);
    }

    public function listTransactions(CarbonImmutable $from, CarbonImmutable $to, array $knownReferences): array
    {
        $transactions = [];

        foreach ((array) $this->cache->get(self::INDEX_KEY, []) as $reference) {
            $tx = $this->read((string) $reference);

            if ($tx === null) {
                continue;
            }

            $at = CarbonImmutable::createFromTimestamp((int) $tx['created_at']);

            if ($at->lt($from) || $at->gt($to)) {
                continue;
            }

            $transactions[] = new GatewayTransaction((string) $reference, GatewayPaymentState::from((string) $tx['state']), (int) $tx['amount_minor'], (string) $tx['currency'], (int) $tx['refunded_minor'], $at);
        }

        return $transactions;
    }

    public function detachMethod(string $token): void
    {
        // Nothing is stored at the sandbox for a token.
    }

    /**
     * Called by DeliverSandboxWebhook when it delivers an event: the "bank side" changes at the same moment.
     *
     * @param array<string, mixed> $data
     */
    public function applyBankSide(string $type, array $data): void
    {
        $reference = (string) ($data['reference'] ?? '');
        $tx = $this->read($reference);

        if ($tx === null) {
            return;
        }

        match ($type) {
            'payment.succeeded' => $tx['state'] = GatewayPaymentState::Succeeded->value,
            'payment.failed' => [$tx['state'], $tx['failure_code']] = [GatewayPaymentState::Failed->value, $data['failure_code'] ?? 'declined'],
            'payment.requires_action' => $tx['state'] = GatewayPaymentState::RequiresAction->value,
            'refund.succeeded' => $tx['refunded_minor'] = (int) $tx['refunded_minor'] + (int) ($data['amount_minor'] ?? 0),
            default => null,
        };

        $this->write($reference, $tx);
    }

    /**
     * The signed request a real processor would send for $type. Public so tests and the job build exactly one shape.
     *
     * @param array<string, mixed> $data
     */
    public function signedRequest(string $type, array $data, ?string $eventId = null, ?CarbonImmutable $at = null): WebhookRequest
    {
        $at ??= CarbonImmutable::now();
        $body = (string) json_encode([
            'id' => $eventId ?? 'evt_'.Str::lower(Str::random(24)),
            'type' => $type,
            'created' => $at->getTimestamp(),
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES);

        return new WebhookRequest($body, [self::SIGNATURE_HEADER => $this->signatureHeader($body, $at->getTimestamp())]);
    }

    public function signatureHeader(string $body, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $this->secret());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deliver(string $type, array $data, int $extraDelaySeconds): void
    {
        $delay = (int) config('payments.sandbox.delay_seconds', 2) + $extraDelaySeconds;

        DeliverSandboxWebhook::dispatch($type, $data)->delay(CarbonImmutable::now()->addSeconds($delay))->afterCommit();
    }

    private function secret(): string
    {
        $configured = config('payments.sandbox.secret');

        return is_string($configured) && $configured !== '' ? $configured : hash_hmac('sha256', 'sandbox-payments', (string) config('app.key'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(string $reference): ?array
    {
        $tx = $this->cache->get(self::TX_KEY.$reference);

        return is_array($tx) ? $tx : null;
    }

    /**
     * @param array<string, mixed> $tx
     */
    private function write(string $reference, array $tx): void
    {
        $this->cache->put(self::TX_KEY.$reference, $tx, now()->addDays(7));
    }

    private function remember(string $reference): void
    {
        $index = (array) $this->cache->get(self::INDEX_KEY, []);
        $index[] = $reference;
        $this->cache->put(self::INDEX_KEY, array_slice($index, -500), now()->addDays(7));
    }
}
