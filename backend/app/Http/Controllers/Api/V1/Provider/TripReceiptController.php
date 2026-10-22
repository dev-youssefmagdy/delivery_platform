<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Services\Booking\BookingReceiptService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * GET /provider/bookings/{booking}/receipt: the provider's job receipt (agreed fare, approved adjustments, commission and
 * earnings) of a completed job, from the same immutable snapshot as the customer's receipt.
 */
final class TripReceiptController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly BookingReceiptService $receipts) {}

    public function show(Request $request, string $booking): JsonResponse
    {
        $receipt = $this->receipts->forProvider($this->providerProfile($request), $booking);
        $url = URL::temporarySignedRoute('website.receipt', now()->addMinutes(15), ['receipt' => $receipt->uuid, 'as' => 'provider', 'lang' => app()->getLocale()]);

        return ApiResponse::success(data: $this->receipts->providerView($receipt) + ['html_url' => $url]);
    }
}
