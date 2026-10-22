<?php

namespace App\Integrations\Payments\Contracts;

use App\Enums\Payments\PaymentGatewayName;
use App\Integrations\Payments\Data\GatewayIntent;
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
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * The only way the platform talks to a payment processor (SRS §12.1, ADR-12). A driver turns the vendor's API and
 * callbacks into these neutral calls; nothing outside app/Integrations/Payments knows a vendor's field names.
 *
 * Rules every driver follows:
 * - `createIntent` never reports success: a payment is `paid` only after `verifyWebhook` accepted a signed callback
 *   or `queryStatus` returned a final answer (Plan I8). Intents start pending or requires_action.
 * - `verifyWebhook` is pure with respect to the platform: it checks the signature (constant time) and, when the vendor
 *   signs a timestamp, the replay window, then returns a neutral event. Deduplication by event id happens above it.
 * - Failures of the processor raise PaymentGatewayException; a refused operation never looks like success.
 */
interface PaymentGateway
{
    public function name(): PaymentGatewayName;

    /**
     * Starts an online payment. The customer completes it in the processor's own UI (`client_secret` / `action_url`).
     *
     * @throws PaymentGatewayException
     */
    public function createIntent(PaymentIntentRequest $request): GatewayIntent;

    /**
     * Starts saving a payment method without charging it.
     *
     * @throws PaymentGatewayException
     * @throws UnsupportedPaymentOperationException when the processor saves methods only during a payment
     */
    public function createSetupIntent(SetupIntentRequest $request): GatewayIntent;

    /**
     * Whether `capture()` exists: the processor can authorize first and capture later.
     */
    public function supportsAuthorization(): bool;

    /**
     * @throws UnsupportedPaymentOperationException
     * @throws PaymentGatewayException
     */
    public function capture(string $gatewayReference, Money $amount): void;

    /**
     * @throws InvalidWebhookSignatureException the signature does not match the body
     * @throws WebhookReplayException the signed timestamp is outside the replay window
     */
    public function verifyWebhook(WebhookRequest $request, CarbonImmutable $now): VerifiedWebhook;

    /**
     * Whether a "succeeded" webhook must be confirmed with `queryStatus()` before the payment is marked paid.
     */
    public function confirmsWebhooksByQuery(): bool;

    /**
     * The processor's current truth about one payment, by the reference stored at `createIntent`.
     *
     * @throws PaymentGatewayException
     */
    public function queryStatus(string $gatewayReference): GatewayStatus;

    /**
     * @throws PaymentGatewayException
     * @throws UnsupportedPaymentOperationException
     */
    public function refund(RefundRequest $request): GatewayRefundResult;

    /**
     * Transactions the processor knows in a window, for `payments:reconcile`. Drivers without a listing API answer for
     * the given known references instead.
     *
     * @param list<string> $knownReferences references the platform holds for the window
     * @return list<GatewayTransaction>
     *
     * @throws PaymentGatewayException
     */
    public function listTransactions(CarbonImmutable $from, CarbonImmutable $to, array $knownReferences): array;

    /**
     * Forgets a saved method at the processor (best effort; the platform removes its own row either way).
     */
    public function detachMethod(string $token): void;
}
