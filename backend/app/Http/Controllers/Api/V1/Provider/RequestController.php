<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Offer\ProviderAssignedBookingResource;
use App\Http\Resources\Api\V1\Offer\ProviderRequestCardResource;
use App\Http\Resources\Api\V1\Provider\EligibilityResource;
use App\Models\Booking;
use App\Services\Booking\ProviderActionResolver;
use App\Services\Offers\OfferSubmissionService;
use App\Services\Offers\ProviderRequestService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What a provider sees of bookings (SRS §20.4): the masked request cards before selection, "not for me", and the
 * full booking after the customer chose this provider.
 */
final class RequestController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly ProviderRequestService $requests,
        private readonly OfferSubmissionService $submission,
        private readonly ProviderActionResolver $actions,
    ) {}

    public function available(Request $request): JsonResponse
    {
        $profile = $this->providerProfile($request);
        $result = $this->requests->available($profile);

        $cards = $result['requests']->map(static fn (Booking $booking): array => (new ProviderRequestCardResource(
            $booking,
            $result['offers']->get($booking->id),
            $booking->getAttribute('recipient_distance_m') === null ? null : (int) $booking->getAttribute('recipient_distance_m'),
        ))->resolve())->values()->all();

        return ApiResponse::success(data: $cards, meta: [
            'eligible' => $result['eligibility']->isEligible(),
            'eligibility' => $result['eligibility']->isEligible() ? null : (new EligibilityResource($result['eligibility']))->resolve(),
        ]);
    }

    public function skip(Request $request, string $booking): JsonResponse
    {
        $this->submission->skip($this->providerProfile($request), $booking);

        return ApiResponse::success(__('api.offer.skipped'));
    }

    public function show(Request $request, string $booking): JsonResponse
    {
        $assigned = $this->requests->assigned($this->providerProfile($request), $booking);

        return ApiResponse::success(data: new ProviderAssignedBookingResource($assigned, $this->actions->forProvider($assigned)));
    }
}
