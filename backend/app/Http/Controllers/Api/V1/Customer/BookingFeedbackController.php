<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Trip\RateTripRequest;
use App\Http\Requests\Api\V1\Trip\ReportBookingRequest;
use App\Http\Resources\Api\V1\Trip\RatingResource;
use App\Http\Resources\Api\V1\Trip\ReportResource;
use App\Services\Booking\BookingReportService;
use App\Services\Booking\RatingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * The customer's rating of the provider (once, finished trips, inside the window) and problem reports (SRS §20.3).
 */
final class BookingFeedbackController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly RatingService $ratings,
        private readonly BookingReportService $reports,
    ) {}

    public function rate(RateTripRequest $request, string $booking): JsonResponse
    {
        $rating = $this->ratings->rateProvider($this->authenticatedUser($request), $booking, $request->toDto());

        return ApiResponse::created(__('api.rating.created'), new RatingResource($rating));
    }

    public function report(ReportBookingRequest $request, string $booking): JsonResponse
    {
        $report = $this->reports->reportAsCustomer($this->authenticatedUser($request), $booking, $request->toDto());

        return ApiResponse::created(__('api.report.created'), new ReportResource($report));
    }
}
