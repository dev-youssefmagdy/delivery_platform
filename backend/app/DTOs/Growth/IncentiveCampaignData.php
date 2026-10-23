<?php

namespace App\DTOs\Growth;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Admin input of a provider incentive campaign (quest). Money is integer minor units; times arrive in the admin display timezone.
 */
final readonly class IncentiveCampaignData
{
    /**
     * @param list<int> $serviceCategoryIds
     * @param list<int> $cityIds
     */
    public function __construct(
        public string $name,
        public ?string $nameEn,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public int $targetJobs,
        public int $bonusMinor,
        public int $maxAwardsPerProvider,
        public int $budgetMinor,
        public ?int $minOrderMinor,
        public ?string $minRating,
        public array $serviceCategoryIds,
        public array $cityIds,
        public int $maxJobsPerCustomer,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $local = static fn (mixed $value): CarbonImmutable => CarbonImmutable::createFromFormat('Y-m-d H:i', trim((string) $value), (string) config('admin.display_timezone', 'UTC'))->utc();
        $ids = static fn (mixed $value): array => array_values(array_unique(array_map('intval', array_filter((array) $value, 'is_numeric'))));
        $rating = $request->input('min_rating');

        return new self(
            name: trim((string) $request->input('name')),
            nameEn: is_string($request->input('name_en')) && trim((string) $request->input('name_en')) !== '' ? trim((string) $request->input('name_en')) : null,
            startsAt: $local($request->input('starts_at')),
            endsAt: $local($request->input('ends_at')),
            targetJobs: max(1, (int) $request->input('target_jobs', 1)),
            bonusMinor: (int) $request->input('bonus_minor', 0),
            maxAwardsPerProvider: max(1, (int) $request->input('max_awards_per_provider', 1)),
            budgetMinor: (int) $request->input('budget_minor', 0),
            minOrderMinor: is_numeric($request->input('min_order_minor')) && (int) $request->input('min_order_minor') > 0 ? (int) $request->input('min_order_minor') : null,
            minRating: is_numeric($rating) && (float) $rating > 0 ? number_format((float) $rating, 2, '.', '') : null,
            serviceCategoryIds: $ids($request->input('service_category_ids', [])),
            cityIds: $ids($request->input('city_ids', [])),
            maxJobsPerCustomer: max(1, (int) $request->input('max_jobs_per_customer', 3)),
            lockVersion: is_numeric($request->input('lock_version')) ? (int) $request->input('lock_version') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name, 'name_en' => $this->nameEn, 'starts_at' => $this->startsAt, 'ends_at' => $this->endsAt, 'target_jobs' => $this->targetJobs,
            'bonus_minor' => $this->bonusMinor, 'max_awards_per_provider' => $this->maxAwardsPerProvider, 'budget_minor' => $this->budgetMinor,
            'min_order_minor' => $this->minOrderMinor, 'min_rating' => $this->minRating, 'service_category_ids' => $this->serviceCategoryIds === [] ? null : $this->serviceCategoryIds,
            'city_ids' => $this->cityIds === [] ? null : $this->cityIds, 'max_jobs_per_customer' => $this->maxJobsPerCustomer,
        ];
    }
}
