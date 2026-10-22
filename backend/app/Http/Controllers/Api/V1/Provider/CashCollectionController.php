<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payment\PaymentResource;
use App\Services\Payments\CashPaymentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /provider/bookings/{booking}/cash-collected: the provider confirms they collected the cash of their completed cash
 * booking (the verified cash rule, SRS §6.7). Own booking only; a repeat is answered as a replay.
 */
final class CashCollectionController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly CashPaymentService $cash) {}

    public function collected(Request $request, string $booking): JsonResponse
    {
        $result = $this->cash->confirm($this->providerProfile($request), $booking);

        return ApiResponse::success(__('api.payment.cash_collected'), new PaymentResource($result['payment']), ['replayed' => $result['replayed']]);
    }
}
