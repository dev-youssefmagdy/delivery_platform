<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payout\StorePayoutRequest;
use App\Http\Resources\Api\V1\Payout\PayoutResource;
use App\Services\Payout\PayoutService;
use App\Services\Payout\ProviderBalanceService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's payouts (SRS §12.3, §20.4): history, one request, asking for a payout and cancelling a request nobody approved
 * yet. A request reserves the amount in the ledger in the same transaction (so it cannot be paid twice), needs a verified payout
 * method and an Idempotency-Key. `meta.balance` on the list tells the app what can be withdrawn and the limits.
 */
final class PayoutController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly PayoutService $payouts,
        private readonly ProviderBalanceService $balances,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $provider = $this->providerProfile($request);
        $balance = $this->balances->forProvider($provider);

        return ApiResponse::paginated(
            $this->payouts->page($provider, $request->integer('per_page') ?: 20),
            PayoutResource::class,
            meta: ['balance' => [
                'currency' => $balance->currency,
                'wallet_minor' => $balance->walletMinor,
                'reserved_minor' => $balance->reservedMinor,
                'available_minor' => $balance->availableMinor,
                'min_minor' => $balance->minMinor,
                'max_minor' => $balance->maxMinor,
            ]],
        );
    }

    public function show(Request $request, string $payout): JsonResponse
    {
        return ApiResponse::success(data: new PayoutResource($this->payouts->find($this->providerProfile($request), $payout)));
    }

    public function store(StorePayoutRequest $request): JsonResponse
    {
        return ApiResponse::created(__('api.payout.requested'), new PayoutResource($this->payouts->request($this->providerProfile($request), $request->toDto())));
    }

    public function cancel(Request $request, string $payout): JsonResponse
    {
        return ApiResponse::success(__('api.payout.cancelled'), new PayoutResource($this->payouts->cancel($this->providerProfile($request), $payout)));
    }
}
