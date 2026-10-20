<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Provider\EligibilityResource;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /provider/eligibility: why the driver can or cannot work right now (typed reasons).
 */
final class EligibilityController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly ProviderOnboardingService $onboarding,
        private readonly ProviderEligibilityService $eligibility,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request));

        if ($profile === null) {
            return ApiResponse::success(data: ['eligible' => false, 'reasons' => [['reason' => 'not_approved', 'reference' => null]]]);
        }

        return ApiResponse::success(data: new EligibilityResource($this->eligibility->check($profile)));
    }
}
