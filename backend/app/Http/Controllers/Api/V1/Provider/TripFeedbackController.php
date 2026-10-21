<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
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
 * The provider's rating of the customer (only when the setting allows it) and problem reports about a booking.
 */
final class TripFeedbackController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly RatingService $ratings,
        private readonly BookingReportService $reports,
    ) {}

    public function rate(RateTripRequest $request, string $booking): JsonResponse
    {
        $rating = $this->ratings->rateCustomer($this->providerProfile($request), $booking, $request->toDto());

        return ApiResponse::created(__('api.rating.created'), new RatingResource($rating));
    }

    public function report(ReportBookingRequest $request, string $booking): JsonResponse
    {
        $report = $this->reports->reportAsProvider($this->providerProfile($request), $booking, $request->toDto());

        return ApiResponse::created(__('api.report.created'), new ReportResource($report));
    }
}
