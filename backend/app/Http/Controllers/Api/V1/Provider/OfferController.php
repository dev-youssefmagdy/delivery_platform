<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\DTOs\Booking\OfferSubmissionResult;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Offer\AcceptCustomerFareRequest;
use App\Http\Requests\Api\V1\Offer\SubmitOfferRequest;
use App\Http\Resources\Api\V1\Offer\ProviderOfferResource;
use App\Models\BookingOffer;
use App\Services\Offers\OfferSubmissionService;
use App\Services\Offers\ProviderRequestService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's side of the negotiation (SRS §20.4): accept the customer's fare, counter-offer (or revise),
 * withdraw, and list its own offers with their statuses. Thin: one service call each.
 */
final class OfferController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly OfferSubmissionService $submission,
        private readonly ProviderRequestService $requests,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $offers = $this->requests->offersOf($this->providerProfile($request));

        return ApiResponse::success(data: $offers->map(static fn (BookingOffer $offer): array => (new ProviderOfferResource($offer))->resolve())->values()->all());
    }

    public function acceptCustomerFare(AcceptCustomerFareRequest $request, string $booking): JsonResponse
    {
        return $this->respond($this->submission->acceptCustomerFare($this->providerProfile($request), $booking, $request->toDto()));
    }

    public function store(SubmitOfferRequest $request, string $booking): JsonResponse
    {
        return $this->respond($this->submission->counterOffer($this->providerProfile($request), $booking, $request->toDto()));
    }

    public function withdraw(Request $request, string $offer): JsonResponse
    {
        $withdrawn = $this->submission->withdraw($this->providerProfile($request), $offer);

        return ApiResponse::success(__('api.offer.withdrawn'), new ProviderOfferResource($withdrawn));
    }

    private function respond(OfferSubmissionResult $result): JsonResponse
    {
        $resource = new ProviderOfferResource($result->offer, $result->fareHints, $result->earningsPreview, $result->replayed);
        $message = __($result->revision ? 'api.offer.revised' : 'api.offer.submitted');

        return $result->replayed || $result->revision
            ? ApiResponse::success($message, $resource, ['replayed' => $result->replayed])
            : ApiResponse::created($message, $resource, ['replayed' => false]);
    }
}
