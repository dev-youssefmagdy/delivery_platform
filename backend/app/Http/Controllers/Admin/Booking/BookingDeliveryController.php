<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Booking;
use App\Services\Booking\BookingDeliveryAdminService;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Delivery and load" tab of a booking and its personal-data endpoints (P4.4): the audited contact reveal and the signed,
 * audited streams of proof pictures and request attachments. All three need `bookings.view_pii`; the tab itself only needs
 * `bookings.view` and withholds contacts, free text and pictures from admins without it.
 */
final class BookingDeliveryController extends AdminController
{
    public function __construct(private readonly BookingDeliveryAdminService $delivery) {}

    public function tab(Booking $booking): View
    {
        $booking->loadMissing('stops');

        return view('admin.bookings.tabs.delivery', [
            'delivery' => $this->delivery->view($booking, Gate::allows('viewPii', $booking)),
            'booking' => $booking,
            'canManage' => Gate::allows('manage', $booking),
            'canViewPii' => Gate::allows('viewPii', $booking),
        ]);
    }

    public function reveal(Booking $booking): JsonResponse
    {
        $booking->loadMissing('stops');

        return ApiResponse::success(data: $this->delivery->revealContacts($booking, $this->actor()));
    }

    public function proof(Booking $booking, string $proof): StreamedResponse
    {
        return $this->delivery->streamProof($booking, $proof, $this->actor());
    }

    public function attachment(Booking $booking, string $attachment): StreamedResponse
    {
        return $this->delivery->streamAttachment($booking, $attachment, $this->actor());
    }
}
