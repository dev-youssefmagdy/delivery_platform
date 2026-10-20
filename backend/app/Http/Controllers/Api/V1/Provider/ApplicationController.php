<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreApplicationRequest;
use App\Http\Requests\Api\V1\Provider\UpdateApplicationRequest;
use App\Http\Resources\Api\V1\Provider\ApplicationResource;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in driver's own application. There is no id in any path: nobody else's application is addressable.
 */
final class ApplicationController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly ProviderOnboardingService $onboarding) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request));

        return ApiResponse::success(data: $profile === null ? ApplicationResource::notStarted() : $this->resource($profile));
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $profile = $this->onboarding->start($this->authenticatedUser($request), $request->toDto());

        return ApiResponse::created(__('api.provider.application_started'), $this->resource($profile));
    }

    public function update(UpdateApplicationRequest $request): JsonResponse
    {
        $profile = $this->onboarding->update($this->authenticatedUser($request), $request->toDto());

        return ApiResponse::success(__('api.provider.application_updated'), $this->resource($profile));
    }

    public function submit(Request $request): JsonResponse
    {
        $profile = $this->onboarding->submit($this->authenticatedUser($request));

        return ApiResponse::success(__('api.provider.application_submitted'), $this->resource($profile));
    }

    private function resource(ProviderProfile $profile): ApplicationResource
    {
        return new ApplicationResource($this->onboarding->progressOf($profile), $this->onboarding->servicesOf($profile));
    }
}
