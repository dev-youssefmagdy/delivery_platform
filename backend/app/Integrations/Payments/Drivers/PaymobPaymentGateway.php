<?php

namespace App\Integrations\Payments\Drivers;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentMethodType;
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
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Paymob Accept (accept.paymob.com), a widely used Egyptian processor: cards and mobile wallets through the unified
 * checkout ("Intention API").
 *
 * IMPLEMENTED FROM THE VENDOR DOCUMENTATION AND NOT TESTED AGAINST THE LIVE SERVICE (no merchant credentials exist in
 * this repository). Treat every request/response field below as "verify against a Paymob test account before go-live"
 * (docs/INTEGRATIONS.md lists the merchant requirements and environment variables). Credentials come from the
 * environment only and are never logged.
 *
 * Flow:
 * - createIntent: `POST /v1/intention/` (Authorization `Token <secret key>`) with the amount in cents, the integration
 *   id of the chosen method, our payment uuid as `special_reference` and our webhook URL as `notification_url`. The app
 *   opens `/unifiedcheckout/?publicKey=..&clientSecret=..`. The gateway reference we keep is our own payment uuid
 *   (Paymob echoes it as `order.merchant_order_id`).
 * - Webhook ("transaction processed callback"): JSON `{type: "TRANSACTION", obj: {...}}` with an `hmac` query
 *   parameter = HMAC-SHA512 (key = HMAC secret) over the documented fields concatenated in the documented order.
 *   Paymob signs no timestamp, so the replay protection is the unique event id; a "succeeded" callback is additionally
 *   confirmed by a transaction inquiry before the payment is marked paid (`confirmsWebhooksByQuery`).
 * - queryStatus: `POST /api/auth/tokens` (API key) then `POST /api/ecommerce/orders/transaction_inquiry` by merchant order id.
 * - refund: `POST /api/acceptance/void_refund/refund` with the transaction id and the amount in cents.
 * - Saving a card happens during a payment (the customer ticks "save card"; a `TOKEN` callback follows), so a standalone
 *   setup intent is not offered.
 */
final class PaymobPaymentGateway implements PaymentGateway
{
    /** Field order of the documented transaction callback HMAC (dotted = nested under `obj`). */
    private const TRANSACTION_HMAC_FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
        'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
        'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
    ];

    /** Field order of the documented card-token callback HMAC. */
    private const TOKEN_HMAC_FIELDS = ['card_subtype', 'created_at', 'email', 'id', 'masked_pan', 'merchant_id', 'order_id', 'token'];

    public function __construct(private readonly HttpFactory $http) {}

    public function name(): PaymentGatewayName
    {
        return PaymentGatewayName::Paymob;
    }

    public function createIntent(PaymentIntentRequest $request): GatewayIntent
    {
        $integration = $request->method === PaymentMethodType::MobileWallet
            ? (int) $this->config('wallet_integration_id')
            : (int) $this->config('card_integration_id');

        if ($integration <= 0) {
            throw new PaymentGatewayException('Paymob integration id is not configured for this method.');
        }

        $name = trim((string) $request->customerName) !== '' ? trim((string) $request->customerName) : 'NA';
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'NA');

        $body = [
            'amount' => $request->amount->amount,
            'currency' => $request->amount->currency->code,
            'payment_methods' => [$integration],
            'items' => [[
                'name' => mb_substr($request->description, 0, 50),
                'amount' => $request->amount->amount,
                'description' => mb_substr($request->description, 0, 255),
                'quantity' => 1,
            ]],
            'billing_data' => [
                'first_name' => $first,
                'last_name' => $last,
                'phone_number' => $request->customerPhone ?? 'NA',
                'email' => $request->customerEmail ?? 'na@example.com',
                'apartment' => 'NA', 'floor' => 'NA', 'street' => 'NA', 'building' => 'NA', 'city' => 'NA', 'country' => 'EG', 'state' => 'NA',
            ],
            'special_reference' => $request->reference,
            'notification_url' => (string) $this->config('notification_url'),
            'redirection_url' => $request->returnUrl ?? (string) config('payments.return_url'),
        ];

        if ($request->savedMethodToken !== null) {
            $body['card_tokens'] = [$request->savedMethodToken];
        }

        $response = $this->send(fn () => $this->client()->withHeaders(['Authorization' => 'Token '.$this->secret('secret_key')])
            ->post($this->url('/v1/intention/'), $body));

        $clientSecret = $response->json('client_secret');

        if (! is_string($clientSecret) || $clientSecret === '') {
            throw new PaymentGatewayException('Paymob did not return a client secret.');
        }

        $publicKey = (string) $this->config('public_key');

        return new GatewayIntent(
            reference: $request->reference,
            status: PaymentStatus::RequiresAction,
            clientSecret: $clientSecret,
            actionUrl: $this->url('/unifiedcheckout/').'?'.http_build_query(['publicKey' => $publicKey, 'clientSecret' => $clientSecret]),
            meta: [
                'gateway_intention_id' => is_scalar($response->json('id')) ? (string) $response->json('id') : null,
                'gateway_order_id' => is_scalar($response->json('intention_order_id')) ? (string) $response->json('intention_order_id') : null,
            ],
        );
    }

    public function createSetupIntent(SetupIntentRequest $request): GatewayIntent
    {
        throw new UnsupportedPaymentOperationException('Paymob saves a card during a payment; there is no standalone setup intent.');
    }

    public function supportsAuthorization(): bool
    {
        return false;
    }

    public function capture(string $gatewayReference, Money $amount): void
    {
        throw new UnsupportedPaymentOperationException('Authorization and capture are not used with Paymob.');
    }

    public function verifyWebhook(WebhookRequest $request, CarbonImmutable $now): VerifiedWebhook
    {
        $received = strtolower((string) ($request->query['hmac'] ?? ''));

        /** @var array<string, mixed>|null $event */
        $event = json_decode($request->body, true);

        if ($received === '' || ! is_array($event) || ! is_array($event['obj'] ?? null)) {
            throw new InvalidWebhookSignatureException('Missing signature or body.');
        }

        /** @var array<string, mixed> $obj */
        $obj = $event['obj'];
        $type = (string) ($event['type'] ?? 'TRANSACTION');
        $fields = $type === 'TOKEN' ? self::TOKEN_HMAC_FIELDS : self::TRANSACTION_HMAC_FIELDS;
        $message = '';

        foreach ($fields as $field) {
            $message .= $this->scalarString($this->dig($obj, $field));
        }

        if (! hash_equals(hash_hmac('sha512', $message, $this->secret('hmac_secret')), $received)) {
            throw new InvalidWebhookSignatureException('Signature mismatch.');
        }

        return $type === 'TOKEN' ? $this->tokenEvent($obj) : $this->transactionEvent($obj, $now);
    }

    public function confirmsWebhooksByQuery(): bool
    {
        return true;
    }

    public function queryStatus(string $gatewayReference): GatewayStatus
    {
        $transaction = $this->inquire($gatewayReference);

        if ($transaction === null) {
            return new GatewayStatus(GatewayPaymentState::NotFound);
        }

        return new GatewayStatus(
            $this->stateOf($transaction),
            (int) ($transaction['amount_cents'] ?? 0),
            (string) ($transaction['currency'] ?? 'EGP'),
            ($transaction['is_refunded'] ?? false) === true ? (int) ($transaction['refunded_amount_cents'] ?? 0) : 0,
            isset($transaction['data']['message']) ? mb_substr((string) $transaction['data']['message'], 0, 64) : null,
        );
    }

    public function refund(RefundRequest $request): GatewayRefundResult
    {
        $transactionId = $request->transactionId;

        if ($transactionId === null) {
            $transaction = $this->inquire($request->paymentReference);
            $transactionId = isset($transaction['id']) ? (string) $transaction['id'] : null;
        }

        if ($transactionId === null) {
            throw new PaymentGatewayException('Paymob transaction not found for this payment.');
        }

        $response = $this->send(fn () => $this->client()->withHeaders(['Authorization' => 'Token '.$this->secret('secret_key')])
            ->post($this->url('/api/acceptance/void_refund/refund'), [
                'transaction_id' => $transactionId,
                'amount_cents' => $request->amount->amount,
            ]));

        $reference = (string) ($response->json('id') ?? $request->refundReference);

        if ($response->json('success') === true || $response->json('success') === 'true') {
            // The final confirmation still arrives as a callback; until then the refund is processing.
            return new GatewayRefundResult($reference, GatewayRefundResult::PROCESSING);
        }

        return new GatewayRefundResult($reference, GatewayRefundResult::FAILED, mb_substr((string) ($response->json('data.message') ?? 'refund_rejected'), 0, 64));
    }

    public function listTransactions(CarbonImmutable $from, CarbonImmutable $to, array $knownReferences): array
    {
        // No dependable listing API is relied on: the platform's own references are inquired one by one.
        $transactions = [];

        foreach ($knownReferences as $reference) {
            $transaction = $this->inquire($reference);

            if ($transaction === null) {
                continue;
            }

            $transactions[] = new GatewayTransaction(
                $reference,
                $this->stateOf($transaction),
                (int) ($transaction['amount_cents'] ?? 0),
                (string) ($transaction['currency'] ?? 'EGP'),
                ($transaction['is_refunded'] ?? false) === true ? (int) ($transaction['refunded_amount_cents'] ?? 0) : 0,
                isset($transaction['created_at']) ? CarbonImmutable::parse((string) $transaction['created_at']) : null,
            );
        }

        return $transactions;
    }

    public function detachMethod(string $token): void
    {
        // Paymob card tokens are only usable by this merchant account; there is nothing to revoke remotely.
    }

    /**
     * @param array<string, mixed> $obj
     */
    private function transactionEvent(array $obj, CarbonImmutable $now): VerifiedWebhook
    {
        $success = ($obj['success'] ?? false) === true;
        $pending = ($obj['pending'] ?? false) === true;
        $refunded = ($obj['is_refunded'] ?? false) === true;
        $voided = ($obj['is_voided'] ?? false) === true;

        $kind = match (true) {
            $voided => WebhookEventKind::Unknown,
            $refunded => $success ? WebhookEventKind::RefundSucceeded : WebhookEventKind::RefundFailed,
            $pending => WebhookEventKind::PaymentRequiresAction,
            $success => WebhookEventKind::PaymentSucceeded,
            default => WebhookEventKind::PaymentFailed,
        };

        $order = is_array($obj['order'] ?? null) ? $obj['order'] : [];
        $eventId = 'txn-'.$this->scalarString($obj['id'] ?? '').'-'.($success ? 's' : 'f').($pending ? 'p' : '').($refunded ? 'r' : '').($voided ? 'v' : '');

        return new VerifiedWebhook(
            eventId: $eventId,
            kind: $kind,
            occurredAt: isset($obj['created_at']) ? CarbonImmutable::parse((string) $obj['created_at']) : $now,
            gatewayReference: isset($order['merchant_order_id']) ? (string) $order['merchant_order_id'] : null,
            amountMinor: isset($obj['amount_cents']) ? (int) $obj['amount_cents'] : null,
            currency: isset($obj['currency']) ? (string) $obj['currency'] : null,
            failureCode: ! $success && ! $pending ? mb_substr((string) ($obj['data']['message'] ?? 'declined'), 0, 64) : null,
            refundReference: $refunded ? $this->scalarString($obj['id'] ?? '') : null,
            payload: [
                'id' => $obj['id'] ?? null, 'success' => $success, 'pending' => $pending, 'is_refunded' => $refunded, 'is_voided' => $voided,
                'amount_cents' => $obj['amount_cents'] ?? null, 'currency' => $obj['currency'] ?? null,
                'order_id' => $order['id'] ?? null, 'merchant_order_id' => $order['merchant_order_id'] ?? null,
                'integration_id' => $obj['integration_id'] ?? null, 'created_at' => $obj['created_at'] ?? null,
            ],
        );
    }

    /**
     * @param array<string, mixed> $obj
     */
    private function tokenEvent(array $obj): VerifiedWebhook
    {
        $pan = $this->scalarString($obj['masked_pan'] ?? '');

        return new VerifiedWebhook(
            eventId: 'token-'.$this->scalarString($obj['id'] ?? ''),
            kind: WebhookEventKind::MethodSaved,
            occurredAt: isset($obj['created_at']) ? CarbonImmutable::parse((string) $obj['created_at']) : CarbonImmutable::now(),
            gatewayReference: isset($obj['order_id']) ? 'order:'.$this->scalarString($obj['order_id']) : null,
            methodToken: isset($obj['token']) ? (string) $obj['token'] : null,
            methodBrand: isset($obj['card_subtype']) ? mb_strtolower((string) $obj['card_subtype']) : null,
            methodLast4: preg_match('/(\d{4})$/', $pan, $m) === 1 ? $m[1] : null,
            setupReference: isset($obj['order_id']) ? 'order:'.$this->scalarString($obj['order_id']) : null,
            payload: ['id' => $obj['id'] ?? null, 'order_id' => $obj['order_id'] ?? null, 'card_subtype' => $obj['card_subtype'] ?? null],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function inquire(string $merchantOrderId): ?array
    {
        $token = $this->authToken();
        $response = $this->send(fn () => $this->client()->post($this->url('/api/ecommerce/orders/transaction_inquiry'), [
            'auth_token' => $token,
            'merchant_order_id' => $merchantOrderId,
        ]), allowNotFound: true);

        if ($response->status() === 404) {
            return null;
        }

        $body = $response->json();

        return is_array($body) && isset($body['id']) ? $body : null;
    }

    private function authToken(): string
    {
        $response = $this->send(fn () => $this->client()->post($this->url('/api/auth/tokens'), ['api_key' => $this->secret('api_key')]));
        $token = $response->json('token');

        return is_string($token) && $token !== '' ? $token : throw new PaymentGatewayException('Paymob authentication failed.');
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function stateOf(array $transaction): GatewayPaymentState
    {
        return match (true) {
            ($transaction['pending'] ?? false) === true => GatewayPaymentState::Pending,
            ($transaction['success'] ?? false) === true => GatewayPaymentState::Succeeded,
            default => GatewayPaymentState::Failed,
        };
    }

    /**
     * @param callable(): Response $call
     */
    private function send(callable $call, bool $allowNotFound = false): Response
    {
        try {
            $response = $call();
        } catch (Throwable) {
            // The original message can contain the URL or request body: never forward it.
            throw new PaymentGatewayException('Paymob could not be reached.');
        }

        if ($response->status() === 404 && $allowNotFound) {
            return $response;
        }

        if (! $response->successful()) {
            throw new PaymentGatewayException('Paymob rejected the request (HTTP '.$response->status().').');
        }

        return $response;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http->timeout(15)->acceptJson()->asJson();
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->config('base_url'), '/').$path;
    }

    private function config(string $key): mixed
    {
        return config('payments.paymob.'.$key);
    }

    private function secret(string $key): string
    {
        $value = $this->config($key);

        return is_string($value) && $value !== '' ? $value : throw new PaymentGatewayException("Paymob setting [{$key}] is not configured.");
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dig(array $data, string $path): mixed
    {
        $value = $data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function scalarString(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => '',
        };
    }
}
