<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Provider\ProviderJobSummaryResource;
use App\Services\Booking\ProviderJobHistoryService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The provider's paginated job history (`GET /provider/bookings`, newest first). Read-only and status-safe: a
 * suspended provider can still see what they did.
 */
final class JobHistoryController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly ProviderJobHistoryService $history) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: null;

        return ApiResponse::paginated($this->history->page($this->providerProfile($request), $perPage), ProviderJobSummaryResource::class);
    }
}
