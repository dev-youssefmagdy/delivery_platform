<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Enums\Common\ActorType;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Courier\CustodyEventRequest;
use App\Http\Requests\Api\V1\Courier\ResolveDeliveryAttemptRequest;
use App\Http\Requests\Api\V1\Courier\StoreDeliveryAttemptRequest;
use App\Http\Requests\Api\V1\Courier\StoreProofRequest;
use App\Http\Requests\Api\V1\Courier\VerifyRecipientOtpRequest;
use App\Http\Resources\Api\V1\Courier\BookingProofResource;
use App\Http\Resources\Api\V1\Courier\DeliveryAttemptResource;
use App\Http\Resources\Api\V1\Offer\ProviderAssignedBookingResource;
use App\Models\Booking;
use App\Models\BookingProof;
use App\Services\Booking\ProviderActionResolver;
use App\Services\Courier\CustodyService;
use App\Services\Courier\DeliveryAttemptService;
use App\Services\Courier\DeliveryProofService;
use App\Services\Courier\ProofAccessService;
use App\Services\Courier\RecipientOtpService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The provider's side of a parcel job (SRS §5.1-C, §7.5, §20.4): proofs, custody steps, the recipient code and failed
 * delivery attempts. Thin: one service call each; every write is `idempotent` and ownership-checked in the service (another
 * provider's booking is a 404). State-changing calls answer with the booking as the provider sees it, with fresh
 * `allowed_actions`.
 */
final class DeliveryController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(
        private readonly DeliveryProofService $proofs,
        private readonly ProofAccessService $access,
        private readonly CustodyService $custody,
        private readonly RecipientOtpService $recipientOtps,
        private readonly DeliveryAttemptService $attempts,
        private readonly ProviderActionResolver $actions,
    ) {}

    public function proofs(Request $request, string $booking): JsonResponse
    {
        $result = $this->access->listForProvider($this->providerProfile($request), $booking);

        return ApiResponse::success(data: $result['proofs']->map(static fn (BookingProof $proof): array => (new BookingProofResource($proof, $result['booking'], ActorType::Provider))->resolve())->values()->all());
    }

    public function storeProof(StoreProofRequest $request, string $booking): JsonResponse
    {
        $provider = $this->providerProfile($request);
        $result = $this->proofs->store($provider, $booking, $request->toDto());
        $model = $this->access->listForProvider($provider, $booking)['booking'];

        return ApiResponse::success(
            __('api.courier.proof_recorded'),
            ['proof' => (new BookingProofResource($result['proof'], $model, ActorType::Provider))->resolve(), 'booking' => $this->resource($model)->resolve()],
            ['replayed' => $result['replayed']],
            $result['replayed'] ? 200 : 201,
        );
    }

    public function proofFile(Request $request, string $booking, string $proof): StreamedResponse
    {
        return $this->access->fileForProvider($this->providerProfile($request), $booking, $proof);
    }

    public function custody(CustodyEventRequest $request, string $booking): JsonResponse
    {
        $result = $this->custody->report($this->providerProfile($request), $booking, $request->event());

        return ApiResponse::success(__('api.courier.custody_recorded'), $this->resource($result['booking']), ['replayed' => $result['replayed']]);
    }

    public function verifyRecipientOtp(VerifyRecipientOtpRequest $request, string $booking): JsonResponse
    {
        $result = $this->recipientOtps->verify($this->providerProfile($request), $booking, $request->code());

        return ApiResponse::success(__('api.courier.recipient_otp_verified'), $this->resource($result['booking']), ['replayed' => $result['replayed']]);
    }

    public function resendRecipientOtp(Request $request, string $booking): JsonResponse
    {
        $provider = $this->providerProfile($request);
        $this->recipientOtps->resend($provider, $booking);

        return ApiResponse::success(__('api.courier.recipient_otp_resent'), $this->resource($this->access->listForProvider($provider, $booking)['booking']));
    }

    public function storeAttempt(StoreDeliveryAttemptRequest $request, string $booking): JsonResponse
    {
        $result = $this->attempts->report($this->providerProfile($request), $booking, $request->toDto());

        return ApiResponse::success(
            __('api.courier.attempt_reported'),
            ['attempt' => (new DeliveryAttemptResource($result['attempt']))->resolve(), 'booking' => $this->resource($result['booking'])->resolve()],
            ['replayed' => $result['replayed']],
            $result['replayed'] ? 200 : 201,
        );
    }

    public function resolveAttempt(ResolveDeliveryAttemptRequest $request, string $booking, string $attempt): JsonResponse
    {
        $result = $this->attempts->resolveByProvider($this->providerProfile($request), $booking, $attempt, $request->resolution());

        return ApiResponse::success(
            __('api.courier.attempt_resolved'),
            ['attempt' => (new DeliveryAttemptResource($result['attempt']))->resolve(), 'booking' => $this->resource($result['booking'])->resolve()],
            ['replayed' => $result['replayed']],
        );
    }

    private function resource(Booking $booking): ProviderAssignedBookingResource
    {
        $booking->load(['customer', 'stops']);

        return new ProviderAssignedBookingResource($booking, $this->actions->forProvider($booking));
    }
}
