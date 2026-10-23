<?php

namespace App\Services\Growth;

use App\DTOs\System\FlagContext;
use App\Enums\Growth\ReferralAudience;
use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use App\Models\User;
use App\Services\System\FeatureFlagService;

/**
 * The single gate of the growth features (ADR-197): every endpoint, hook and job asks here, and everything is OFF until an admin turns
 * the flag on (`referrals`, `loyalty`, `incentives`). A switched-off feature answers 404 `feature_disabled` and posts nothing.
 */
final class GrowthFeatures
{
    public function __construct(private readonly FeatureFlagService $flags) {}

    public function enabled(FeatureFlagKey $key, AppTarget $app, ?int $cityId = null, ?User $user = null): bool
    {
        return $this->flags->isEnabled($key, new FlagContext($app, $cityId, $user?->uuid));
    }

    public function referrals(ReferralAudience $audience, ?int $cityId = null, ?User $user = null): bool
    {
        return $this->enabled(FeatureFlagKey::Referrals, $audience === ReferralAudience::Provider ? AppTarget::Provider : AppTarget::Customer, $cityId, $user);
    }
}
