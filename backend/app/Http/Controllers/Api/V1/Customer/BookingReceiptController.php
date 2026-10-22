<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Booking\BookingReceiptService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * GET /bookings/{booking}/receipt (SRS §6.7): the immutable receipt of a completed booking as the customer sees it, with a
 * short-lived signed link to the printable HTML receipt (`html_url`, Arabic RTL by default, `?lang=en` for English).
 */
final class BookingReceiptController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly BookingReceiptService $receipts) {}

    public function show(Request $request, string $booking): JsonResponse
    {
        $receipt = $this->receipts->forCustomer($this->authenticatedUser($request), $booking);
        $url = URL::temporarySignedRoute('website.receipt', now()->addMinutes(15), ['receipt' => $receipt->uuid, 'as' => 'customer', 'lang' => app()->getLocale()]);

        return ApiResponse::success(data: $this->receipts->customerView($receipt) + ['html_url' => $url]);
    }
}
