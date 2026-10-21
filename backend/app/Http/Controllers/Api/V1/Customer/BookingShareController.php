<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Booking\ShareLinkService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Share trip (SRS §6.6, §20.3): create a time-limited public link, or stop sharing. The token is returned once.
 */
final class BookingShareController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly ShareLinkService $links) {}

    public function store(Request $request, string $booking): JsonResponse
    {
        $share = $this->links->create($this->authenticatedUser($request), $booking);

        return ApiResponse::created(__('api.share.created'), [
            'url' => $share['url'],
            'expires_at' => $share['expires_at']->toIso8601ZuluString(),
        ]);
    }

    public function destroy(Request $request, string $booking): JsonResponse
    {
        $revoked = $this->links->revoke($this->authenticatedUser($request), $booking);

        return ApiResponse::success(__('api.share.revoked'), ['revoked' => $revoked]);
    }
}
