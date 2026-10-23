<?php

namespace App\Http\Controllers\Api\V1\Safety;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Safety\TripShareToContactsService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Share the running trip with the trusted contacts who agreed to receive it (both apps).
 */
final class TripShareController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly TripShareToContactsService $shares) {}

    public function store(Request $request, string $booking): JsonResponse
    {
        $result = $this->shares->share($this->authenticatedUser($request), $this->clientApp($request), $booking);

        return ApiResponse::created(__('safety.api.trip_shared', ['sent' => $result['sent']]), [
            'url' => $result['url'],
            'expires_at' => $result['expires_at']->toIso8601ZuluString(),
            'contacts' => ['total' => $result['total'], 'sent' => $result['sent'], 'failed' => $result['failed'], 'skipped' => $result['skipped']],
        ]);
    }

    public function destroy(Request $request, string $booking): JsonResponse
    {
        return ApiResponse::success(__('safety.api.trip_share_stopped'), ['revoked' => $this->shares->stop($this->authenticatedUser($request), $this->clientApp($request), $booking)]);
    }
}
