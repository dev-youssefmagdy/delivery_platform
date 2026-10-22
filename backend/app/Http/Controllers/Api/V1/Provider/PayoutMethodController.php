<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payout\StorePayoutMethodRequest;
use App\Http\Resources\Api\V1\Payout\PayoutMethodResource;
use App\Services\Payout\PayoutMethodService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's payout methods (SRS §7.6): enrol, change, make default and remove. Finance verifies a method before it can be
 * used; changing its details drops the verification. Only the type, label and last digits are ever returned.
 */
final class PayoutMethodController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly PayoutMethodService $methods) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: PayoutMethodResource::collection($this->methods->list($this->providerProfile($request))));
    }

    public function store(StorePayoutMethodRequest $request): JsonResponse
    {
        return ApiResponse::created(__('api.payout.method_added'), new PayoutMethodResource($this->methods->create($this->providerProfile($request), $request->toDto())));
    }

    public function update(StorePayoutMethodRequest $request, string $method): JsonResponse
    {
        return ApiResponse::success(__('api.payout.method_updated'), new PayoutMethodResource($this->methods->update($this->providerProfile($request), $method, $request->toDto())));
    }

    public function makeDefault(Request $request, string $method): JsonResponse
    {
        return ApiResponse::success(__('api.payout.method_default'), new PayoutMethodResource($this->methods->makeDefault($this->providerProfile($request), $method)));
    }

    public function destroy(Request $request, string $method): JsonResponse
    {
        $this->methods->delete($this->providerProfile($request), $method);

        return ApiResponse::success(__('api.payout.method_removed'));
    }
}
