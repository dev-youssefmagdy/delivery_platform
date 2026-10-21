<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Exceptions\ConflictException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreLocationRequest;
use App\Services\Location\LocationIngestService;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * POST /provider/location: rate limited, one sample or a buffered batch.
 */
final class LocationController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly ProviderOnboardingService $onboarding,
        private readonly LocationIngestService $ingest,
    ) {}

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request)) ?? throw ConflictException::make('location_not_tracking');
        $result = $this->ingest->ingest($profile, $request->samples());

        return ApiResponse::success(__('api.provider.location_received'), [
            'received' => $result->received,
            'applied' => $result->applied,
            'flagged' => $result->flagged,
            'stale' => $result->stale,
            'tracking' => [
                'state' => $result->trackingState->value,
                'interval_seconds' => $result->trackingState->intervalSeconds(),
            ],
        ]);
    }
}
