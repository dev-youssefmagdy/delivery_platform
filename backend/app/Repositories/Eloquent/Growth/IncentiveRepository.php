<?php

namespace App\Repositories\Eloquent\Growth;

use App\DTOs\Growth\CampaignFilterData;
use App\DTOs\Growth\ProgressFilterData;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Models\IncentiveAward;
use App\Models\IncentiveCampaign;
use App\Models\IncentiveProgress;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IncentiveRepository implements IncentiveRepositoryInterface
{
    public function createCampaign(array $attributes): IncentiveCampaign
    {
        return IncentiveCampaign::query()->create($attributes);
    }

    public function updateCampaign(IncentiveCampaign $campaign, array $attributes): IncentiveCampaign
    {
        $campaign->forceFill($attributes)->save();

        return $campaign;
    }

    public function lockCampaign(int $id): ?IncentiveCampaign
    {
        return IncentiveCampaign::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findCampaign(int $id): ?IncentiveCampaign
    {
        return IncentiveCampaign::query()->find($id);
    }

    public function campaignsQuery(CampaignFilterData $filters): Builder
    {
        return IncentiveCampaign::query()
            ->select('incentive_campaigns.*')
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('status', array_map(static fn (CampaignStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('starts_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('starts_at', '<=', $filters->to?->endOfDay()));
    }

    public function openCampaigns(CarbonInterface $at): Collection
    {
        return IncentiveCampaign::query()->where('status', CampaignStatus::Active->value)->where('starts_at', '<=', $at)->where('ends_at', '>', $at)->orderBy('id')->get();
    }

    public function lockOrCreateProgress(int $campaignId, int $providerId): IncentiveProgress
    {
        DB::table('incentive_progress')->insertOrIgnore([
            'uuid' => (string) Str::uuid(), 'campaign_id' => $campaignId, 'provider_id' => $providerId, 'status' => IncentiveProgressStatus::InProgress->value,
            'completed_jobs' => 0, 'awards_count' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return IncentiveProgress::query()->where('campaign_id', $campaignId)->where('provider_id', $providerId)->lockForUpdate()->firstOrFail();
    }

    public function lockProgress(int $id): ?IncentiveProgress
    {
        return IncentiveProgress::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function updateProgress(IncentiveProgress $progress, array $attributes): IncentiveProgress
    {
        $progress->forceFill($attributes)->save();

        return $progress;
    }

    public function incrementCustomerJobs(int $progressId, int $customerId): int
    {
        DB::table('incentive_customer_jobs')->insertOrIgnore(['progress_id' => $progressId, 'customer_id' => $customerId, 'jobs' => 0]);
        DB::table('incentive_customer_jobs')->where('progress_id', $progressId)->where('customer_id', $customerId)->increment('jobs');

        return (int) DB::table('incentive_customer_jobs')->where('progress_id', $progressId)->where('customer_id', $customerId)->value('jobs');
    }

    public function progressOfProvider(int $providerId): Collection
    {
        return IncentiveProgress::query()->where('provider_id', $providerId)->with('campaign')->get();
    }

    public function progressQuery(ProgressFilterData $filters): Builder
    {
        return IncentiveProgress::query()
            ->select('incentive_progress.*')
            ->with(['campaign:id,name,name_en,target_jobs,currency', 'provider.user:id,name'])
            ->when($filters->campaignId !== null, static fn (Builder $q) => $q->where('campaign_id', $filters->campaignId))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('status', array_map(static fn (IncentiveProgressStatus $case): string => $case->value, $filters->statuses)));
    }

    public function createAward(array $attributes): IncentiveAward
    {
        return IncentiveAward::query()->create($attributes);
    }

    public function exhausted(int $limit): Collection
    {
        return IncentiveProgress::query()->where('status', IncentiveProgressStatus::Exhausted->value)->orderBy('id')->limit($limit)->get();
    }

    public function held(): Collection
    {
        return IncentiveProgress::query()->where('status', IncentiveProgressStatus::Held->value)->get();
    }

    public function campaignTotals(int $campaignId): array
    {
        return [
            'providers' => IncentiveProgress::query()->where('campaign_id', $campaignId)->count(),
            'awards' => IncentiveAward::query()->where('campaign_id', $campaignId)->count(),
            'held' => IncentiveProgress::query()->where('campaign_id', $campaignId)->where('status', IncentiveProgressStatus::Held->value)->count(),
            'completed_jobs' => (int) IncentiveProgress::query()->where('campaign_id', $campaignId)->sum('completed_jobs'),
        ];
    }
}
