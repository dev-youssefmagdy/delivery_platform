<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Enums\Common\ActorType;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Load\BookingAttachmentResource;
use App\Models\BookingAttachment;
use App\Services\Scope\BookingAttachmentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The load pictures, videos and documents of the provider's OWN job (staged disclosure, Plan I10): before selection, and for another
 * provider's booking, the answer is a 404.
 */
final class BookingAttachmentController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly BookingAttachmentService $attachments) {}

    public function index(Request $request, string $booking): JsonResponse
    {
        $provider = $this->providerProfile($request);
        $model = $this->attachments->bookingOfProvider($provider, $booking);

        return ApiResponse::success(data: $this->attachments->listForProvider($provider, $booking)
            ->map(static fn (BookingAttachment $attachment): array => (new BookingAttachmentResource($attachment, $model, ActorType::Provider))->resolve())->values()->all());
    }

    public function file(Request $request, string $booking, string $attachment): StreamedResponse
    {
        return $this->attachments->stream($this->attachments->bookingOfProvider($this->providerProfile($request), $booking), $attachment);
    }
}
