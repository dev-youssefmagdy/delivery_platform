<?php

namespace App\Repositories\Contracts\Growth;

use App\DTOs\Growth\CampaignFilterData;
use App\DTOs\Growth\ProgressFilterData;
use App\Models\IncentiveAward;
use App\Models\IncentiveCampaign;
use App\Models\IncentiveProgress;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface IncentiveRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createCampaign(array $attributes): IncentiveCampaign;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateCampaign(IncentiveCampaign $campaign, array $attributes): IncentiveCampaign;

    public function lockCampaign(int $id): ?IncentiveCampaign;

    public function findCampaign(int $id): ?IncentiveCampaign;

    /**
     * @return Builder<IncentiveCampaign>
     */
    public function campaignsQuery(CampaignFilterData $filters): Builder;

    /**
     * Active campaigns whose window contains $at.
     *
     * @return Collection<int, IncentiveCampaign>
     */
    public function openCampaigns(CarbonInterface $at): Collection;

    /**
     * Creates the progress row when missing and returns it locked for update.
     */
    public function lockOrCreateProgress(int $campaignId, int $providerId): IncentiveProgress;

    public function lockProgress(int $id): ?IncentiveProgress;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateProgress(IncentiveProgress $progress, array $attributes): IncentiveProgress;

    /**
     * Counts one more job of a customer for this progress row and returns the new count.
     */
    public function incrementCustomerJobs(int $progressId, int $customerId): int;

    /**
     * @return Collection<int, IncentiveProgress>
     */
    public function progressOfProvider(int $providerId): Collection;

    /**
     * @return Builder<IncentiveProgress>
     */
    public function progressQuery(ProgressFilterData $filters): Builder;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAward(array $attributes): IncentiveAward;

    /**
     * Progress rows stopped by an empty budget (retried when the budget is raised).
     *
     * @return Collection<int, IncentiveProgress>
     */
    public function exhausted(int $limit): Collection;

    /**
     * @return Collection<int, IncentiveProgress>
     */
    public function held(): Collection;

    /**
     * @return array{providers: int, awards: int, held: int, completed_jobs: int}
     */
    public function campaignTotals(int $campaignId): array;
}
