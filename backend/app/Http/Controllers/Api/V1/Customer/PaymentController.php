<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\ServiceUnavailableException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StartPaymentRequest;
use App\Http\Resources\Api\V1\Payment\PaymentResource;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Services\Payments\PaymentIntentService;
use App\Services\Payments\PaymentStatusSyncService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Online payments of the customer's own bookings (SRS §12.1, §20.5): start the trip payment, tip, and ask the platform to
 * re-check a payment after returning from the payment page. Nothing here can mark a payment paid: starting returns a
 * pending / requires-action payment and the refresh only reads the gateway's answer (Plan I8). Writes need an Idempotency-Key.
 */
final class PaymentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly PaymentIntentService $intents,
        private readonly PaymentStatusSyncService $sync,
    ) {}

    public function intent(StartPaymentRequest $request, string $booking): JsonResponse
    {
        $result = $this->intents->payTrip($this->authenticatedUser($request), $booking, $request->toDto());

        return $result->replayed
            ? ApiResponse::success(__('api.payment.resumed'), new PaymentResource($result))
            : ApiResponse::created(__('api.payment.started'), new PaymentResource($result));
    }

    public function tip(StartPaymentRequest $request, string $booking): JsonResponse
    {
        $result = $this->intents->tip($this->authenticatedUser($request), $booking, $request->toDto());

        return $result->replayed
            ? ApiResponse::success(__('api.payment.resumed'), new PaymentResource($result))
            : ApiResponse::created(__('api.payment.tip_started'), new PaymentResource($result));
    }

    public function refresh(Request $request, string $payment): JsonResponse
    {
        try {
            $refreshed = $this->sync->refresh($this->authenticatedUser($request), $payment);
        } catch (PaymentGatewayException) {
            throw ServiceUnavailableException::make('payment_gateway_unavailable');
        }

        return ApiResponse::success(data: new PaymentResource($refreshed));
    }
}
