<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;
use App\Enums\Provider\EligibilityReason;
use App\Exceptions\ProviderNotEligibleException;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Http\Request;

/**
 * The signed-in provider's profile for the negotiation endpoints; a user without one is simply not approved.
 */
trait ResolvesProviderProfile
{
    private function providerProfile(Request $request): ProviderProfile
    {
        $profile = app(ProviderOnboardingService::class)->profileOf($this->authenticatedUser($request));

        if ($profile === null) {
            throw ProviderNotEligibleException::because(new EligibilityResult([new EligibilityIssue(EligibilityReason::NotApproved)]));
        }

        return $profile;
    }
}
