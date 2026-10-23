<?php

namespace App\Repositories\Eloquent\Growth;

use App\DTOs\Growth\CampaignFilterData;
use App\DTOs\Growth\RelationshipFilterData;
use App\DTOs\Growth\RewardFilterData;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Enums\Growth\RewardRecipient;
use App\Models\ReferralCampaign;
use App\Models\ReferralCode;
use App\Models\ReferralRelationship;
use App\Models\ReferralReward;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReferralRepository implements ReferralRepositoryInterface
{
    public function codeOf(int $userId, ReferralAudience $audience): ?ReferralCode
    {
        return ReferralCode::query()->where('user_id', $userId)->where('audience', $audience->value)->first();
    }

    public function createCode(int $userId, ReferralAudience $audience, string $code): ReferralCode
    {
        return ReferralCode::query()->create(['user_id' => $userId, 'audience' => $audience, 'code' => $code]);
    }

    public function findCode(string $code): ?ReferralCode
    {
        return ReferralCode::query()->where('code', $code)->first();
    }

    public function activeCampaign(ReferralAudience $audience, CarbonInterface $at): ?ReferralCampaign
    {
        return ReferralCampaign::query()
            ->where('audience', $audience->value)
            ->where('status', CampaignStatus::Active->value)
            ->where(static fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(static fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->orderByDesc('id')
            ->first();
    }

    public function createCampaign(array $attributes): ReferralCampaign
    {
        return ReferralCampaign::query()->create($attributes);
    }

    public function updateCampaign(ReferralCampaign $campaign, array $attributes): ReferralCampaign
    {
        $campaign->forceFill($attributes)->save();

        return $campaign;
    }

    public function lockCampaign(int $id): ?ReferralCampaign
    {
        return ReferralCampaign::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findCampaign(int $id): ?ReferralCampaign
    {
        return ReferralCampaign::query()->find($id);
    }

    public function campaignsQuery(CampaignFilterData $filters): Builder
    {
        return ReferralCampaign::query()
            ->select('referral_campaigns.*')
            ->when($filters->audiences !== [], static fn (Builder $q) => $q->whereIn('audience', array_map(static fn (ReferralAudience $case): string => $case->value, $filters->audiences)))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('status', array_map(static fn (CampaignStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('starts_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('starts_at', '<=', $filters->to?->endOfDay()));
    }

    public function campaignLookup(string $term, int $limit): Collection
    {
        return ReferralCampaign::query()->when($term !== '', static fn (Builder $q) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))->orderByDesc('id')->limit($limit)->get();
    }

    public function campaignsByIds(array $ids): Collection
    {
        return ReferralCampaign::query()->whereKey($ids)->get();
    }

    public function relationshipOfReferee(int $refereeUserId, ReferralAudience $audience): ?ReferralRelationship
    {
        return ReferralRelationship::query()->where('referee_user_id', $refereeUserId)->where('audience', $audience->value)->first();
    }

    public function lockRelationship(int $id): ?ReferralRelationship
    {
        return ReferralRelationship::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function createRelationship(array $attributes): ReferralRelationship
    {
        return ReferralRelationship::query()->create($attributes);
    }

    public function updateRelationship(ReferralRelationship $relationship, array $attributes): ReferralRelationship
    {
        $relationship->forceFill($attributes)->save();

        return $relationship;
    }

    public function relationshipsOfReferrerSince(int $referrerUserId, ReferralAudience $audience, CarbonInterface $since): int
    {
        return ReferralRelationship::query()->where('referrer_user_id', $referrerUserId)->where('audience', $audience->value)->where('created_at', '>=', $since)->count();
    }

    public function relationshipsOfReferrerInCampaign(int $referrerUserId, int $campaignId): int
    {
        return ReferralRelationship::query()->where('referrer_user_id', $referrerUserId)->where('campaign_id', $campaignId)
            ->where('status', '!=', RelationshipStatus::Rejected->value)->count();
    }

    public function referrerRewardCount(int $referrerUserId, int $campaignId): int
    {
        return ReferralReward::query()->where('user_id', $referrerUserId)->where('campaign_id', $campaignId)->where('recipient', RewardRecipient::Referrer->value)->count();
    }

    public function waitingForReward(int $limit): Collection
    {
        return ReferralRelationship::query()
            ->where('status', RelationshipStatus::Pending->value)->whereNotNull('qualified_at')->with('campaign')
            ->orderBy('qualified_at')->limit($limit)->get();
    }

    public function relationshipsQuery(RelationshipFilterData $filters): Builder
    {
        return ReferralRelationship::query()
            ->select('referral_relationships.*')
            ->with(['campaign:id,name,name_en,currency', 'referrer:id,name', 'referee:id,name'])
            ->when($filters->campaignIds !== [], static fn (Builder $q) => $q->whereIn('campaign_id', $filters->campaignIds))
            ->when($filters->audiences !== [], static fn (Builder $q) => $q->whereIn('audience', array_map(static fn (ReferralAudience $case): string => $case->value, $filters->audiences)))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('status', array_map(static fn (RelationshipStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->flagged === true, static fn (Builder $q) => $q->whereNotNull('fraud_flags'))
            ->when($filters->flagged === false, static fn (Builder $q) => $q->whereNull('fraud_flags'))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('created_at', '<=', $filters->to?->endOfDay()));
    }

    public function relationshipsOfReferrer(int $referrerUserId, ReferralAudience $audience, int $limit): Collection
    {
        return ReferralRelationship::query()->where('referrer_user_id', $referrerUserId)->where('audience', $audience->value)
            ->with('referee:id,name')->latest('id')->limit($limit)->get();
    }

    public function referrerCounts(int $referrerUserId, ReferralAudience $audience): array
    {
        $rows = ReferralRelationship::query()->where('referrer_user_id', $referrerUserId)->where('audience', $audience->value)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $count = static fn (RelationshipStatus $status): int => (int) ($rows[$status->value] ?? 0);

        return [
            'referred' => (int) $rows->sum(),
            'qualified' => $count(RelationshipStatus::Rewarded),
            'under_review' => $count(RelationshipStatus::UnderReview),
        ];
    }

    public function createReward(array $attributes): ReferralReward
    {
        return ReferralReward::query()->create($attributes);
    }

    public function findRewardByKey(string $key): ?ReferralReward
    {
        return ReferralReward::query()->where('idempotency_key', $key)->first();
    }

    public function updateReward(ReferralReward $reward, array $attributes): ReferralReward
    {
        $reward->forceFill($attributes)->save();

        return $reward;
    }

    public function rewardsOfUser(int $userId, int $limit): Collection
    {
        return ReferralReward::query()->where('user_id', $userId)->latest('id')->limit($limit)->get();
    }

    public function rewardsQuery(RewardFilterData $filters): Builder
    {
        return ReferralReward::query()
            ->select('referral_rewards.*')
            ->with(['campaign:id,name,name_en', 'user:id,name', 'relationship:id,uuid'])
            ->when($filters->campaignIds !== [], static fn (Builder $q) => $q->whereIn('campaign_id', $filters->campaignIds))
            ->when($filters->recipients !== [], static fn (Builder $q) => $q->whereIn('recipient', array_map(static fn (RewardRecipient $case): string => $case->value, $filters->recipients)))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('status', array_map(static fn (CreditStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('granted_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('granted_at', '<=', $filters->to?->endOfDay()));
    }

    public function usersWithDueRewards(CarbonInterface $now, int $limit): array
    {
        return ReferralReward::query()->where('status', CreditStatus::Granted->value)->where('wallet_type', 'customer_wallet')
            ->whereNotNull('expires_at')->where('expires_at', '<=', $now)->distinct()->limit($limit)->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)->all();
    }

    public function underReview(): Collection
    {
        return ReferralRelationship::query()->where('status', RelationshipStatus::UnderReview->value)->get();
    }

    public function campaignTotals(int $campaignId): array
    {
        $rows = ReferralRelationship::query()->where('campaign_id', $campaignId)->selectRaw('status, count(*) as total, sum(qualified_at is not null) as qualified')->groupBy('status')->get();
        $by = static fn (RelationshipStatus $status): int => (int) ($rows->firstWhere('status', $status)?->total ?? 0);

        return [
            'relationships' => (int) $rows->sum('total'),
            'qualified' => (int) $rows->sum('qualified'),
            'rewarded' => $by(RelationshipStatus::Rewarded),
            'under_review' => $by(RelationshipStatus::UnderReview),
            'rejected' => $by(RelationshipStatus::Rejected),
            'spent_minor' => (int) ReferralCampaign::query()->whereKey($campaignId)->value('spent_minor'),
        ];
    }

    public function outcomesReport(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $rows = DB::table('referral_campaigns as c')
            ->leftJoin('referral_relationships as r', static function ($join) use ($from, $to): void {
                $join->on('r.campaign_id', '=', 'c.id');
                $from === null || $join->where('r.created_at', '>=', $from);
                $to === null || $join->where('r.created_at', '<=', $to);
            })
            ->groupBy('c.id', 'c.name', 'c.audience', 'c.status', 'c.currency', 'c.budget_minor', 'c.spent_minor')
            ->orderBy('c.id')
            ->selectRaw("c.id, c.name, c.audience, c.status, c.currency, c.budget_minor, c.spent_minor, count(r.id) as referrals,
                sum(r.qualified_at is not null) as qualified, sum(r.status = 'rewarded') as rewarded, sum(r.status = 'under_review') as under_review,
                sum(r.status = 'rejected') as rejected")
            ->get();

        return $rows->map(static function (object $row): array {
            $referrals = (int) $row->referrals;

            return [
                'campaign' => (string) $row->name,
                'audience' => (string) $row->audience,
                'status' => (string) $row->status,
                'currency' => (string) $row->currency,
                'referrals' => $referrals,
                'qualified' => (int) $row->qualified,
                'rewarded' => (int) $row->rewarded,
                'under_review' => (int) $row->under_review,
                'rejected' => (int) $row->rejected,
                'conversion_bp' => $referrals === 0 ? 0 : intdiv((int) $row->rewarded * 10000, $referrals),
                'spent_minor' => (int) $row->spent_minor,
                'budget_minor' => (int) $row->budget_minor,
            ];
        })->all();
    }
}
