<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\Common\ActorType;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Load\UploadBookingAttachmentRequest;
use App\Http\Resources\Api\V1\Load\BookingAttachmentResource;
use App\Models\BookingAttachment;
use App\Services\Scope\BookingAttachmentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photos, videos and documents of the customer's moving / freight requests (SRS §5.1-D, §5.1-E). Upload first, then reference the id
 * from the service form (`photos`, `documents`); files are streamed only through signed routes behind the owner's token.
 */
final class BookingAttachmentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly BookingAttachmentService $attachments) {}

    public function store(UploadBookingAttachmentRequest $request): JsonResponse
    {
        $attachment = $this->attachments->upload($this->authenticatedUser($request), $request->file('file'), $request->kind());

        return ApiResponse::success(__('api.load.attachment_uploaded'), (new BookingAttachmentResource($attachment))->resolve(), status: 201);
    }

    public function destroy(Request $request, string $attachment): JsonResponse
    {
        $this->attachments->discard($this->authenticatedUser($request), $attachment);

        return ApiResponse::success(__('api.load.attachment_removed'));
    }

    public function index(Request $request, string $booking): JsonResponse
    {
        $customer = $this->authenticatedUser($request);
        $model = $this->attachments->bookingOfCustomer($customer, $booking);

        return ApiResponse::success(data: $this->attachments->listForCustomer($customer, $booking)
            ->map(static fn (BookingAttachment $attachment): array => (new BookingAttachmentResource($attachment, $model, ActorType::Customer))->resolve())->values()->all());
    }

    public function file(Request $request, string $booking, string $attachment): StreamedResponse
    {
        return $this->attachments->stream($this->attachments->bookingOfCustomer($this->authenticatedUser($request), $booking), $attachment);
    }
}
