<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\ConflictException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Middleware\Idempotency;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Http\Resources\Api\V1\Offer\CustomerOfferResource;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Booking\BookingActionResolver;
use App\Services\Booking\BookingReadService;
use App\Services\Offers\CustomerOfferService;
use App\Services\Offers\OfferSelectionService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Offer cards and the choice of a provider (SRS §20.3). Choosing needs an active account and an Idempotency-Key. A
 * conflict that means "the booking moved on" (already assigned, offer expired or gone) answers 409 together with the
 * fresh booking, so the app can redraw without another request.
 */
final class BookingOfferController extends Controller
{
    use ResolvesClientApp;

    private const REFRESH_CODES = ['booking_already_assigned', 'offer_expired', 'offer_not_active', 'offer_provider_unavailable'];

    public function __construct(
        private readonly CustomerOfferService $offers,
        private readonly OfferSelectionService $selection,
        private readonly BookingReadService $reader,
        private readonly BookingActionResolver $actions,
        private readonly BookingOfferRepositoryInterface $summary,
    ) {}

    public function index(Request $request, string $booking): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $model = $this->reader->show($user, $booking);
        Gate::authorize('view', $model);

        return ApiResponse::success(
            data: $this->offers->openOffers($user, $booking)->map(static fn (BookingOffer $offer): array => (new CustomerOfferResource($offer))->resolve())->values()->all(),
            meta: ['booking_status' => $model->status->value, 'search_expires_at' => $model->search_expires_at?->toIso8601ZuluString()],
        );
    }

    public function accept(Request $request, string $booking, string $offer): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        try {
            $result = $this->selection->accept($user, $booking, $offer, $request->header(Idempotency::HEADER));
        } catch (ConflictException $conflict) {
            return $this->conflict($request, $booking, $conflict);
        }

        return ApiResponse::success(__('api.offer.selected'), $this->resource($result->booking->load(['stops', 'statusEvents'])), ['replayed' => $result->replayed]);
    }

    public function reject(Request $request, string $booking, string $offer): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        try {
            $this->selection->reject($user, $booking, $offer);
        } catch (ConflictException $conflict) {
            return $this->conflict($request, $booking, $conflict);
        }

        return ApiResponse::success(__('api.offer.rejected'), $this->resource($this->reader->show($user, $booking)));
    }

    private function conflict(Request $request, string $bookingUuid, ConflictException $conflict): JsonResponse
    {
        if (! in_array($conflict->errorCode, self::REFRESH_CODES, true)) {
            throw $conflict;
        }

        $fresh = $this->reader->show($this->authenticatedUser($request), $bookingUuid);

        return ApiResponse::error(
            $conflict->getMessage(),
            $conflict->getStatusCode(),
            $conflict->errorCode,
            meta: $conflict->meta() + ['booking' => $this->resource($fresh)->resolve()],
        );
    }

    private function resource(Booking $booking): BookingResource
    {
        return new BookingResource($booking, $this->actions->forCustomer($booking), $this->summary->summary($booking), $this->offers->selectedOffer($booking));
    }
}
