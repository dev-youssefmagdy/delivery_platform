<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\CreateBookingRequest;
use App\Http\Requests\Api\V1\Booking\ListBookingsRequest;
use App\Http\Requests\Api\V1\Booking\UpdateBookingRequestRequest;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Http\Resources\Api\V1\Booking\BookingSummaryResource;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Booking\BookingActionResolver;
use App\Services\Booking\BookingReadService;
use App\Services\Booking\CreateBookingService;
use App\Services\Booking\UpdateBookingRequestService;
use App\Services\Offers\CustomerOfferService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Customer booking endpoints (SRS §20.3). Thin: authorize, build the DTO, call one service, return a resource.
 * Reads are status-safe (a suspended customer can still see history); creating and editing need an active account
 * (`account.active:strict`, set on the routes) and an Idempotency-Key.
 */
final class BookingController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly CreateBookingService $creator,
        private readonly UpdateBookingRequestService $updater,
        private readonly BookingReadService $reader,
        private readonly BookingActionResolver $actions,
        private readonly BookingOfferRepositoryInterface $offers,
        private readonly CustomerOfferService $selected,
    ) {}

    public function index(ListBookingsRequest $request): JsonResponse
    {
        $page = $this->reader->list($this->authenticatedUser($request), $request->filters(), $request->perPage());

        return ApiResponse::paginated($page, BookingSummaryResource::class);
    }

    public function show(Request $request, string $booking): JsonResponse
    {
        $model = $this->reader->show($this->authenticatedUser($request), $booking);
        Gate::authorize('view', $model);

        return ApiResponse::success(data: $this->resource($model));
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $result = $this->creator->create($this->authenticatedUser($request), $request->toDto());

        return ApiResponse::created(__('api.booking.created'), $this->resource($result->booking), ['replayed' => $result->replayed]);
    }

    public function updateRequest(UpdateBookingRequestRequest $request, string $booking): JsonResponse
    {
        $model = $this->updater->update($this->authenticatedUser($request), $booking, $request->toDto());

        return ApiResponse::success(__('api.booking.request_updated'), $this->resource($model));
    }

    private function resource(Booking $booking): BookingResource
    {
        return new BookingResource($booking, $this->actions->forCustomer($booking), $this->offers->summary($booking), $this->selected->selectedOffer($booking));
    }
}
