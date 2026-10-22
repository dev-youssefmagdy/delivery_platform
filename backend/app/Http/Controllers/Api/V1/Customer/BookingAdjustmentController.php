<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Adjustment\AnswerBookingAdjustmentRequest;
use App\Http\Requests\Api\V1\Adjustment\CreateBookingAdjustmentRequest;
use App\Http\Resources\Api\V1\Adjustment\BookingAdjustmentResource;
use App\Services\Booking\BookingAdjustmentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's side of fare adjustments (SRS §9.4): list, ask (extra stop / scope change), answer the provider's
 * requests and withdraw their own. Only the other party's requests can be approved or rejected; every write carries an
 * Idempotency-Key. Thin: one service call each.
 */
final class BookingAdjustmentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly BookingAdjustmentService $adjustments) {}

    public function index(Request $request, string $booking): JsonResponse
    {
        return ApiResponse::success(data: BookingAdjustmentResource::collection($this->adjustments->listForCustomer($this->authenticatedUser($request), $booking)));
    }

    public function store(CreateBookingAdjustmentRequest $request, string $booking): JsonResponse
    {
        $adjustment = $this->adjustments->requestByCustomer($this->authenticatedUser($request), $booking, $request->toDto());

        return ApiResponse::success(__('api.adjustment.requested'), new BookingAdjustmentResource($adjustment), status: 201);
    }

    public function approve(AnswerBookingAdjustmentRequest $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->approveByCustomer($this->authenticatedUser($request), $booking, $adjustment, $request->note());

        return ApiResponse::success(__('api.adjustment.approved'), new BookingAdjustmentResource($result));
    }

    public function reject(AnswerBookingAdjustmentRequest $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->rejectByCustomer($this->authenticatedUser($request), $booking, $adjustment, $request->note());

        return ApiResponse::success(__('api.adjustment.rejected'), new BookingAdjustmentResource($result));
    }

    public function withdraw(Request $request, string $booking, string $adjustment): JsonResponse
    {
        $result = $this->adjustments->withdrawByCustomer($this->authenticatedUser($request), $booking, $adjustment);

        return ApiResponse::success(__('api.adjustment.withdrawn'), new BookingAdjustmentResource($result));
    }
}
