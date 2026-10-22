<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\Coupon\CouponAttemptContext;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\ValidateCouponRequest;
use App\Http\Resources\Api\V1\Coupon\CouponQuoteResource;
use App\Http\Resources\Api\V1\Coupon\CouponSummaryResource;
use App\Http\Resources\Api\V1\Coupon\CouponUsageResource;
use App\Services\Coupon\CouponQuoteService;
use App\Services\Coupon\CustomerCouponService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer coupon endpoints (SRS §20.5). `validate` is a quote: it reserves nothing and always answers 200 with the decision
 * and an explanation code (an ineligible coupon is an answer, not an error); the reservation happens when the booking is created.
 */
final class CouponController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly CouponQuoteService $quotes,
        private readonly CustomerCouponService $customerCoupons,
    ) {}

    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $quote = $this->quotes->quote($this->authenticatedUser($request), $request->toDto(), CouponAttemptContext::Validate);

        return ApiResponse::success(data: new CouponQuoteResource($quote));
    }

    public function index(Request $request): JsonResponse
    {
        $overview = $this->customerCoupons->overview($this->authenticatedUser($request));

        return ApiResponse::success(data: [
            'available' => CouponSummaryResource::collection($overview['available']),
            'history' => CouponUsageResource::collection($overview['history']),
        ]);
    }
}
