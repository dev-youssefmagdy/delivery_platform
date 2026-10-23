<?php

namespace App\DTOs\Growth;

use App\Enums\Growth\ReferralAudience;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Admin input of a referral campaign. Money is integer minor units; times arrive in the admin display timezone and are stored in UTC.
 */
final readonly class ReferralCampaignData
{
    /**
     * @param list<int> $serviceCategoryIds
     * @param list<int> $cityIds
     */
    public function __construct(
        public string $name,
        public ?string $nameEn,
        public ReferralAudience $audience,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public int $referrerRewardMinor,
        public int $refereeRewardMinor,
        public int $milestoneJobs,
        public ?int $minOrderMinor,
        public array $serviceCategoryIds,
        public array $cityIds,
        public int $creditValidDays,
        public int $perReferrerCap,
        public int $budgetMinor,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $local = static function (mixed $value): ?CarbonImmutable {
            return is_string($value) && trim($value) !== ''
                ? CarbonImmutable::createFromFormat('Y-m-d H:i', trim($value), (string) config('admin.display_timezone', 'UTC'))->utc()
                : null;
        };
        $ids = static fn (mixed $value): array => array_values(array_unique(array_map('intval', array_filter((array) $value, 'is_numeric'))));
        $optionalInt = static fn (mixed $value): ?int => is_numeric($value) && (int) $value > 0 ? (int) $value : null;

        return new self(
            name: trim((string) $request->input('name')),
            nameEn: is_string($request->input('name_en')) && trim((string) $request->input('name_en')) !== '' ? trim((string) $request->input('name_en')) : null,
            audience: ReferralAudience::tryFromValue($request->input('audience')) ?? ReferralAudience::Customer,
            startsAt: $local($request->input('starts_at')),
            endsAt: $local($request->input('ends_at')),
            referrerRewardMinor: (int) $request->input('referrer_reward_minor', 0),
            refereeRewardMinor: (int) $request->input('referee_reward_minor', 0),
            milestoneJobs: max(1, (int) $request->input('milestone_jobs', 1)),
            minOrderMinor: $optionalInt($request->input('min_order_minor')),
            serviceCategoryIds: $ids($request->input('service_category_ids', [])),
            cityIds: $ids($request->input('city_ids', [])),
            creditValidDays: max(1, (int) $request->input('credit_valid_days', 30)),
            perReferrerCap: max(1, (int) $request->input('per_referrer_cap', 20)),
            budgetMinor: (int) $request->input('budget_minor', 0),
            lockVersion: is_numeric($request->input('lock_version')) ? (int) $request->input('lock_version') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name, 'name_en' => $this->nameEn, 'audience' => $this->audience, 'starts_at' => $this->startsAt, 'ends_at' => $this->endsAt,
            'referrer_reward_minor' => $this->referrerRewardMinor, 'referee_reward_minor' => $this->refereeRewardMinor, 'milestone_jobs' => $this->milestoneJobs,
            'min_order_minor' => $this->minOrderMinor, 'service_category_ids' => $this->serviceCategoryIds === [] ? null : $this->serviceCategoryIds,
            'city_ids' => $this->cityIds === [] ? null : $this->cityIds, 'credit_valid_days' => $this->creditValidDays, 'per_referrer_cap' => $this->perReferrerCap,
            'budget_minor' => $this->budgetMinor,
        ];
    }
}
