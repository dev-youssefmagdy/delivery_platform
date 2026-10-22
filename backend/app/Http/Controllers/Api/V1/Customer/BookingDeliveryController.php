<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\Common\ActorType;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Courier\ResolveDeliveryAttemptRequest;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Http\Resources\Api\V1\Courier\BookingProofResource;
use App\Http\Resources\Api\V1\Courier\DeliveryAttemptResource;
use App\Models\Booking;
use App\Models\BookingProof;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Booking\BookingActionResolver;
use App\Services\Courier\DeliveryAttemptService;
use App\Services\Courier\ProofAccessService;
use App\Services\Offers\CustomerOfferService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The customer's side of a parcel (SRS §5.1-C, §7.5): the proofs of their own booking (pictures through signed URLs only) and
 * the decision after a failed delivery attempt. Another customer's booking is a 404.
 */
final class BookingDeliveryController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly ProofAccessService $access,
        private readonly DeliveryAttemptService $attempts,
        private readonly BookingActionResolver $actions,
        private readonly BookingOfferRepositoryInterface $offers,
        private readonly CustomerOfferService $selected,
    ) {}

    public function proofs(Request $request, string $booking): JsonResponse
    {
        $result = $this->access->listForCustomer($this->authenticatedUser($request), $booking);

        return ApiResponse::success(data: $result['proofs']->map(static fn (BookingProof $proof): array => (new BookingProofResource($proof, $result['booking'], ActorType::Customer))->resolve())->values()->all());
    }

    public function proofFile(Request $request, string $booking, string $proof): StreamedResponse
    {
        return $this->access->fileForCustomer($this->authenticatedUser($request), $booking, $proof);
    }

    public function resolveAttempt(ResolveDeliveryAttemptRequest $request, string $booking, string $attempt): JsonResponse
    {
        $result = $this->attempts->resolveByCustomer($this->authenticatedUser($request), $booking, $attempt, $request->resolution());
        $model = $result['booking']->load(['stops', 'statusEvents']);

        return ApiResponse::success(
            __('api.courier.attempt_resolved'),
            ['attempt' => (new DeliveryAttemptResource($result['attempt']))->resolve(), 'booking' => $this->resource($model)->resolve()],
            ['replayed' => $result['replayed']],
        );
    }

    private function resource(Booking $booking): BookingResource
    {
        return new BookingResource($booking, $this->actions->forCustomer($booking), $this->offers->summary($booking), $this->selected->selectedOffer($booking));
    }
}
