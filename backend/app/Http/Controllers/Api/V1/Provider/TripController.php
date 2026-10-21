<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\DTOs\Booking\TripActionResult;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Trip\ArrivedRequest;
use App\Http\Requests\Api\V1\Trip\CancelBookingRequest;
use App\Http\Requests\Api\V1\Trip\VerifyStartPinRequest;
use App\Http\Resources\Api\V1\Offer\ProviderAssignedBookingResource;
use App\Http\Resources\Api\V1\Trip\CancellationDecisionResource;
use App\Models\Booking;
use App\Services\Booking\CancellationService;
use App\Services\Booking\ProviderActionResolver;
use App\Services\Booking\TripLifecycleService;
use App\Services\Booking\TripPinService;
use App\Services\Offers\ProviderRequestService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's trip actions after selection (SRS §7.5, §20.4): acknowledge, arrived, verify the start PIN, start,
 * complete and cancel, plus the current job. Thin: one service call each. Every write is `idempotent` (Idempotency-Key)
 * and ownership-checked in the service (another provider's booking is a 404); the booking is returned as the provider
 * sees it, with `allowed_actions` computed by the server.
 */
final class TripController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly TripLifecycleService $trip,
        private readonly TripPinService $pins,
        private readonly CancellationService $cancellations,
        private readonly ProviderRequestService $requests,
        private readonly ProviderActionResolver $actions,
    ) {}

    public function active(Request $request): JsonResponse
    {
        $booking = $this->requests->current($this->providerProfile($request));

        return ApiResponse::success(data: $booking === null ? null : $this->resource($booking));
    }

    public function acknowledge(Request $request, string $booking): JsonResponse
    {
        return $this->respond($this->trip->acknowledge($this->providerProfile($request), $booking), 'api.trip.acknowledged');
    }

    public function arrived(ArrivedRequest $request, string $booking): JsonResponse
    {
        return $this->respond($this->trip->arrived($this->providerProfile($request), $booking, $request->toDto()), 'api.trip.arrived');
    }

    public function verifyStartPin(VerifyStartPinRequest $request, string $booking): JsonResponse
    {
        $result = $this->pins->verify($this->providerProfile($request), $booking, $request->pin());

        return $this->respond(new TripActionResult($result['booking'], $result['replayed']), 'api.trip.pin_verified');
    }

    public function start(Request $request, string $booking): JsonResponse
    {
        return $this->respond($this->trip->start($this->providerProfile($request), $booking), 'api.trip.started');
    }

    public function complete(Request $request, string $booking): JsonResponse
    {
        return $this->respond($this->trip->complete($this->providerProfile($request), $booking), 'api.trip.completed');
    }

    public function cancel(CancelBookingRequest $request, string $booking): JsonResponse
    {
        $result = $this->cancellations->cancelByProvider($this->providerProfile($request), $booking, $request->toDto());
        $meta = ['cancellation' => (new CancellationDecisionResource($result->decision))->resolve(), 'committed' => $result->committed, 'redispatched' => $result->redispatched, 'replayed' => $result->replayed];

        if (! $result->committed) {
            return ApiResponse::success(__('api.cancellation.preview'), null, $meta);
        }

        // After a re-dispatch the booking no longer belongs to this provider: nothing of it is returned.
        return ApiResponse::success(
            __($result->redispatched ? 'api.trip.cancelled_redispatch' : 'api.trip.cancelled'),
            $result->redispatched ? null : $this->resource($result->booking),
            $meta,
        );
    }

    private function respond(TripActionResult $result, string $messageKey): JsonResponse
    {
        return ApiResponse::success(__($messageKey), $this->resource($result->booking), ['replayed' => $result->replayed]);
    }

    private function resource(Booking $booking): ProviderAssignedBookingResource
    {
        $booking->load(['customer', 'stops']);

        return new ProviderAssignedBookingResource($booking, $this->actions->forProvider($booking));
    }
}
