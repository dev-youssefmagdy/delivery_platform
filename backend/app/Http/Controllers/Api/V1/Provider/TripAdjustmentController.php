<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Adjustment\AnswerBookingAdjustmentRequest;
use App\Http\Requests\Api\V1\Adjustment\CreateBookingAdjustmentRequest;
use App\Http\Resources\Api\V1\Adjustment\BookingAdjustmentResource;
use App\Services\Booking\BookingAdjustmentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's side of fare adjustments (SRS §9.4): list, ask (extra stop, waiting, toll / parking at cost with
 * evidence, scope change), answer the customer's requests and withdraw their own. Ownership is checked in the service
 * (another provider's booking is a 404).
 */
final class TripAdjustmentController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly BookingAdjustmentService $adjustments) {}

    public function index(Request $request, string $booking): JsonResponse
    {
        return ApiResponse::success(data: BookingAdjustmentResource::collection($this->adjustments->listForProvider($this->providerProfile($request), $booking)));
    }

    public function store(CreateBookingAdjustmentRequest $request, string $booking): JsonResponse
    {
        $adjustment = $this->adjustments->requestByProvider($this->providerProfile($request), $booking, $request->toDto());

        return ApiResponse::success(__('api.adjustment.requested'), new BookingAdjustmentResource($adjustment), status: 201);
    }

    public function approve(AnswerBookingAdjustmentRequest $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->approveByProvider($this->providerProfile($request), $booking, $adjustment, $request->note());

        return ApiResponse::success(__('api.adjustment.approved'), new BookingAdjustmentResource($result));
    }

    public function reject(AnswerBookingAdjustmentRequest $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->rejectByProvider($this->providerProfile($request), $booking, $adjustment, $request->note());

        return ApiResponse::success(__('api.adjustment.rejected'), new BookingAdjustmentResource($result));
    }

    public function withdraw(Request $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->withdrawByProvider($this->providerProfile($request), $booking, $adjustment);

        return ApiResponse::success(__('api.adjustment.withdrawn'), new BookingAdjustmentResource($result));
    }
}
