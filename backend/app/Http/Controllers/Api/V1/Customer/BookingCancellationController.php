<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\ConflictException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Trip\CancelBookingRequest;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Http\Resources\Api\V1\Trip\CancellationDecisionResource;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Booking\BookingActionResolver;
use App\Services\Booking\BookingReadService;
use App\Services\Booking\CancellationService;
use App\Services\Offers\CustomerOfferService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * POST /bookings/{booking}/cancel (SRS §20.3). With `preview=true` nothing is written and the fee the customer would
 * pay is returned (no Idempotency-Key needed); the real call carries the key and, when a fee is due, the amount the
 * customer accepted. If the decision changed between preview and commit the answer is 409 `cancellation_fee_changed`
 * with the fresh decision in `meta.cancellation`.
 */
final class BookingCancellationController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly CancellationService $cancellations,
        private readonly BookingReadService $reader,
        private readonly BookingActionResolver $actions,
        private readonly BookingOfferRepositoryInterface $summary,
        private readonly CustomerOfferService $offers,
    ) {}

    public function cancel(CancelBookingRequest $request, string $booking): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        try {
            $result = $this->cancellations->cancelByCustomer($user, $booking, $request->toDto());
        } catch (ConflictException $conflict) {
            if ($conflict->errorCode === 'cancellation_fee_changed') {
                return ApiResponse::error($conflict->getMessage(), $conflict->getStatusCode(), $conflict->errorCode, meta: $conflict->meta());
            }

            throw $conflict;
        }

        $meta = ['cancellation' => (new CancellationDecisionResource($result->decision))->resolve(), 'committed' => $result->committed, 'replayed' => $result->replayed];

        if (! $result->committed) {
            return ApiResponse::success(__('api.cancellation.preview'), null, $meta);
        }

        $fresh = $this->reader->show($user, $booking);

        return ApiResponse::success(__('api.booking.cancelled'), $this->resource($fresh), $meta);
    }

    private function resource(Booking $booking): BookingResource
    {
        return new BookingResource($booking, $this->actions->forCustomer($booking), $this->summary->summary($booking), $this->offers->selectedOffer($booking));
    }
}
