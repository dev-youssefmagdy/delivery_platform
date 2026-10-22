<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\SetupIntentRequest;
use App\Http\Resources\Api\V1\Payment\PaymentMethodResource;
use App\Services\Payments\PaymentMethodService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's saved payment methods (SRS §20.5). Only brand and last four digits are ever returned.
 */
final class PaymentMethodController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly PaymentMethodService $methods) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: PaymentMethodResource::collection($this->methods->list($this->authenticatedUser($request))));
    }

    public function setupIntent(SetupIntentRequest $request): JsonResponse
    {
        $setup = $this->methods->setup($this->authenticatedUser($request), $request->methodType());

        return ApiResponse::created(__('api.payment.setup_started'), [
            'payment_method' => new PaymentMethodResource($setup['method']),
            'action' => $setup['client_secret'] === null && $setup['action_url'] === null ? null : ['client_secret' => $setup['client_secret'], 'url' => $setup['action_url']],
        ]);
    }

    public function destroy(Request $request, string $paymentMethod): JsonResponse
    {
        $this->methods->remove($this->authenticatedUser($request), $paymentMethod);

        return ApiResponse::success(__('api.payment.method_removed'));
    }
}
