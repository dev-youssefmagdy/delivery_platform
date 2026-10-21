<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;
use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\EligibilityScope;
use App\Exceptions\ProviderNotEligibleException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\GoOfflineRequest;
use App\Http\Requests\Api\V1\Provider\GoOnlineRequest;
use App\Http\Resources\Api\V1\Provider\AvailabilityResource;
use App\Http\Resources\Api\V1\Provider\EligibilityResource;
use App\Models\ProviderProfile;
use App\Services\Provider\AvailabilityService;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The driver home's availability switch: state, go online (422 with typed reasons), go offline.
 */
final class AvailabilityController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly ProviderOnboardingService $onboarding,
        private readonly AvailabilityService $availability,
        private readonly ProviderEligibilityService $eligibility,
    ) {}

    /**
     * GET /provider/availability: the state plus the working eligibility, so the toggle can show why it is disabled.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileOf($request);

        return ApiResponse::success(data: [
            'availability' => new AvailabilityResource($this->availability->stateOf($profile), $profile),
            'eligibility' => new EligibilityResource($this->eligibility->check($profile, null, EligibilityScope::Working)),
        ]);
    }

    public function online(GoOnlineRequest $request): JsonResponse
    {
        $profile = $this->profileOf($request);
        $state = $this->availability->goOnline($profile, $request->vehicleUuid(), $request->sample());

        return ApiResponse::success(__('api.provider.availability_online'), new AvailabilityResource($state, $profile));
    }

    public function offline(GoOfflineRequest $request): JsonResponse
    {
        $profile = $this->profileOf($request);
        $state = $this->availability->goOffline($profile, $request->confirmed());

        return ApiResponse::success(__('api.provider.availability_offline'), new AvailabilityResource($state, $profile));
    }

    private function profileOf(Request $request): ProviderProfile
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request));

        if ($profile === null) {
            throw ProviderNotEligibleException::because(new EligibilityResult([new EligibilityIssue(EligibilityReason::NotApproved)]));
        }

        return $profile;
    }
}
