<?php

namespace App\Services\Growth;

use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use App\Exceptions\FeatureDisabledException;
use App\Models\IncentiveCampaign;
use App\Models\IncentiveProgress;
use App\Models\LoyaltyLevel;
use App\Models\ProviderProfile;
use App\Models\ReferralRelationship;
use App\Models\User;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * What the two apps read (referral share screen, credits, loyalty status, incentive progress). Every method first asks the server
 * flag; a switched-off feature is a 404 `feature_disabled` and the app renders nothing. Fraud review is never revealed: a flagged
 * referral or held quest shows as "pending".
 */
final class GrowthViewService
{
    public function __construct(
        private readonly GrowthFeatures $features,
        private readonly ReferralCodeService $codes,
        private readonly ReferralRepositoryInterface $referrals,
        private readonly IncentiveRepositoryInterface $incentives,
        private readonly CreditPositionService $positions,
        private readonly LoyaltyService $loyalty,
        private readonly ProviderOnboardingService $providers,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function referral(User $user, ReferralAudience $audience): array
    {
        $this->assertReferrals($user, $audience);
        $code = $this->codes->ensure($user, $audience);
        $link = $this->codes->link($code);
        $campaign = $this->referrals->activeCampaign($audience, now());

        return [
            'code' => $code->code,
            'link' => $link,
            'share_text' => __('growth.app.share_text', ['code' => $code->code, 'link' => $link]),
            'campaign' => $campaign === null ? null : [
                'name' => $campaign->displayName(),
                'referrer_reward' => $this->money($campaign->referrer_reward_minor, $campaign->currency),
                'referee_reward' => $this->money($campaign->referee_reward_minor, $campaign->currency),
                'milestone_jobs' => $campaign->milestone_jobs,
                'credit_valid_days' => $audience === ReferralAudience::Customer ? $campaign->credit_valid_days : null,
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
            'can_apply_code' => $this->referrals->relationshipOfReferee($user->id, $audience) === null,
            'stats' => $this->stats($user, $audience),
            'referrals' => $this->referrals->relationshipsOfReferrer($user->id, $audience, 50)->map(fn (ReferralRelationship $row): array => [
                'id' => $row->uuid,
                'name' => Str::before((string) ($row->referee->name ?? ''), ' ') ?: null,
                'status' => $this->publicStatus($row->status),
                'jobs_completed' => $row->jobs_completed,
                'created_at' => $row->created_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function credits(User $user): array
    {
        $this->assertEnabled(FeatureFlagKey::Referrals, AppTarget::Customer, $user);
        $position = $this->positions->forCustomer($user->id);
        $currency = $position['currency'];

        return [
            'available' => $currency === null ? null : $this->money($position['available_minor'], $currency),
            'credits' => array_map(fn (array $credit): array => [
                'source' => $credit['source'],
                'amount' => $this->money($credit['amount_minor'], $credit['currency']),
                'remaining' => $this->money($credit['unspent_minor'], $credit['currency']),
                'status' => $credit['status']->value,
                'granted_at' => $credit['granted_at']->toIso8601String(),
                'expires_at' => $credit['expires_at']?->toIso8601String(),
            ], array_reverse($position['credits'])),
            'note' => __('growth.app.credits_note'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loyaltyStatus(User $user): array
    {
        $this->assertEnabled(FeatureFlagKey::Loyalty, AppTarget::Customer, $user);
        $status = $this->loyalty->statusFor($user);
        $level = static fn (?LoyaltyLevel $row): ?array => $row === null ? null : ['id' => $row->id, 'name' => $row->displayName(), 'min_completed_jobs' => $row->min_completed_jobs];

        return [
            'completed_jobs' => $status['jobs'],
            'level' => $level($status['level']),
            'next_level' => $level($status['next']),
            'jobs_to_next_level' => $status['jobs_to_next'],
            'levels' => $status['levels']->map(fn (LoyaltyLevel $row): array => $level($row) + [
                'reward' => $row->reward_minor > 0 ? $this->money($row->reward_minor, $row->currency) : null,
                'reached' => $row->min_completed_jobs <= $status['jobs'],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incentives(User $user): array
    {
        $profile = $this->providers->profileOf($user);
        $this->assertEnabled(FeatureFlagKey::Incentives, AppTarget::Provider, $user, $profile?->city_id);

        if (! $profile instanceof ProviderProfile) {
            return ['campaigns' => []];
        }

        $progress = $this->incentives->progressOfProvider($profile->id)->keyBy('campaign_id');
        $campaigns = $this->incentives->openCampaigns(now())
            ->filter(static fn (IncentiveCampaign $campaign): bool => $campaign->city_ids === null || $campaign->city_ids === [] || ($profile->city_id !== null && in_array($profile->city_id, $campaign->city_ids, true)))
            ->map(fn (IncentiveCampaign $campaign): array => $this->campaignView($campaign, $progress->get($campaign->id)))
            ->values()->all();

        return ['campaigns' => $campaigns];
    }

    /**
     * @return array{referred: int, qualified: int, pending: int}
     */
    private function stats(User $user, ReferralAudience $audience): array
    {
        $counts = $this->referrals->referrerCounts($user->id, $audience);

        return ['referred' => $counts['referred'], 'qualified' => $counts['qualified'], 'pending' => $counts['referred'] - $counts['qualified']];
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignView(IncentiveCampaign $campaign, ?IncentiveProgress $progress): array
    {
        $jobs = $progress?->completed_jobs ?? 0;
        $awards = $progress?->awards_count ?? 0;
        $done = $awards >= $campaign->max_awards_per_provider;

        return [
            'id' => $campaign->uuid,
            'name' => $campaign->displayName(),
            'starts_at' => $campaign->starts_at->toIso8601String(),
            'ends_at' => $campaign->ends_at->toIso8601String(),
            'target_jobs' => $campaign->target_jobs,
            'bonus' => $this->money($campaign->bonus_minor, $campaign->currency),
            'max_awards' => $campaign->max_awards_per_provider,
            'min_rating' => $campaign->min_rating,
            'progress' => [
                'jobs_in_cycle' => $done ? $campaign->target_jobs : $jobs % max(1, $campaign->target_jobs),
                'completed_jobs' => $jobs,
                'awards' => $awards,
                'status' => match (true) {
                    $done => 'completed',
                    $progress === null || $progress->status === IncentiveProgressStatus::InProgress => 'in_progress',
                    $progress->status === IncentiveProgressStatus::Rejected => 'closed',
                    default => 'pending',
                },
            ],
        ];
    }

    private function publicStatus(RelationshipStatus $status): string
    {
        return match ($status) {
            RelationshipStatus::Rewarded => 'rewarded',
            RelationshipStatus::Rejected => 'closed',
            default => 'pending',
        };
    }

    private function assertReferrals(User $user, ReferralAudience $audience): void
    {
        $cityId = $audience === ReferralAudience::Provider ? $this->providers->profileOf($user)?->city_id : null;

        if (! $this->features->referrals($audience, $cityId, $user)) {
            throw FeatureDisabledException::make('feature_disabled');
        }
    }

    private function assertEnabled(FeatureFlagKey $key, AppTarget $app, User $user, ?int $cityId = null): void
    {
        if (! $this->features->enabled($key, $app, $cityId, $user)) {
            throw FeatureDisabledException::make('feature_disabled');
        }
    }

    /**
     * @return array{amount_minor: int, currency: string, amount: string}
     */
    private function money(int $minor, string $currency): array
    {
        return Money::of($minor, $this->currencies->get($currency))->toArray();
    }
}
