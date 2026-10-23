<?php

namespace App\Http\Controllers\Api\V1\Growth;

use App\Enums\Growth\ReferralAudience;
use App\Enums\Identity\ClientApp;
use App\Exceptions\FeatureDisabledException;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Growth\ApplyReferralRequest;
use App\Services\Growth\GrowthFeatures;
use App\Services\Growth\GrowthViewService;
use App\Services\Growth\ReferralService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Referral endpoints of both apps (SRS 11.3). The audience is the caller's app (token ability): customers refer customers, providers
 * refer providers. Behind the `referrals` server flag (404 `feature_disabled` when off).
 */
final class ReferralController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly GrowthViewService $views,
        private readonly ReferralService $referrals,
        private readonly GrowthFeatures $features,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(data: $this->views->referral($this->authenticatedUser($request), $this->audience($request)));
    }

    public function apply(ApplyReferralRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $audience = $this->audience($request);

        if (! $this->features->referrals($audience, null, $user)) {
            throw FeatureDisabledException::make('feature_disabled');
        }

        $relationship = $this->referrals->apply($user, $audience, (string) $request->validated('code'));

        return ApiResponse::success(__('growth.app.applied'), ['id' => $relationship->uuid, 'status' => 'pending']);
    }

    private function audience(Request $request): ReferralAudience
    {
        return $this->clientApp($request) === ClientApp::Provider ? ReferralAudience::Provider : ReferralAudience::Customer;
    }
}
