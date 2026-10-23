<?php

namespace App\Console\Commands;

use App\Enums\Growth\ReferralAudience;
use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use App\Services\Growth\GrowthFeatures;
use App\Services\Growth\IncentiveService;
use App\Services\Growth\ReferralMilestoneService;
use Illuminate\Console\Command;

class SettleGrowthRewards extends Command
{
    protected $signature = 'growth:settle';

    protected $description = 'Pay referral rewards and quest bonuses that waited for budget or an active campaign (idempotent)';

    public function handle(ReferralMilestoneService $referrals, IncentiveService $incentives, GrowthFeatures $features): int
    {
        if ($features->referrals(ReferralAudience::Customer) || $features->referrals(ReferralAudience::Provider)) {
            $this->info('Referral rewards paid: '.$referrals->settleWaiting(200));
        }

        if ($features->enabled(FeatureFlagKey::Incentives, AppTarget::Provider)) {
            $this->info('Quest bonuses paid: '.$incentives->settleExhausted(200));
        }

        return self::SUCCESS;
    }
}
