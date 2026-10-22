<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Enums\Payments\PaymentGatewayName;
use App\Http\Controllers\Controller;
use App\Integrations\Payments\Data\WebhookRequest;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /payment-gateways/{gateway}/webhook: a processor's callback. No user authentication: the SIGNATURE is the
 * authentication (checked by the gateway driver over the raw body). The answer carries only a coarse outcome, never
 * payment data; its HTTP status tells the gateway whether to retry (5xx) or not.
 */
final class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookService $webhooks) {}

    public function __invoke(Request $request, string $gateway): JsonResponse
    {
        $name = PaymentGatewayName::tryFrom($gateway) ?? abort(404);

        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            $headers[strtolower((string) $key)] = (string) ($values[0] ?? '');
        }

        $query = array_map(static fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $request->query());
        $receipt = $this->webhooks->receive($name, new WebhookRequest($request->getContent(), $headers, $query));

        return new JsonResponse(['received' => $receipt->httpStatus() < 400, 'outcome' => $receipt->outcome->value], $receipt->httpStatus());
    }
}
