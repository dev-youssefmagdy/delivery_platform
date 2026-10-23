<?php

namespace App\Repositories\Contracts\Growth;

use App\DTOs\Growth\CampaignFilterData;
use App\DTOs\Growth\RelationshipFilterData;
use App\DTOs\Growth\RewardFilterData;
use App\Enums\Growth\ReferralAudience;
use App\Models\ReferralCampaign;
use App\Models\ReferralCode;
use App\Models\ReferralRelationship;
use App\Models\ReferralReward;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ReferralRepositoryInterface
{
    public function codeOf(int $userId, ReferralAudience $audience): ?ReferralCode;

    /**
     * @throws \Illuminate\Database\UniqueConstraintViolationException when the code or the (user, audience) pair exists
     */
    public function createCode(int $userId, ReferralAudience $audience, string $code): ReferralCode;

    public function findCode(string $code): ?ReferralCode;

    /**
     * The newest active campaign of the audience that is inside its window at $at.
     */
    public function activeCampaign(ReferralAudience $audience, CarbonInterface $at): ?ReferralCampaign;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createCampaign(array $attributes): ReferralCampaign;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateCampaign(ReferralCampaign $campaign, array $attributes): ReferralCampaign;

    public function lockCampaign(int $id): ?ReferralCampaign;

    public function findCampaign(int $id): ?ReferralCampaign;

    /**
     * @return Builder<ReferralCampaign>
     */
    public function campaignsQuery(CampaignFilterData $filters): Builder;

    /**
     * @return Collection<int, ReferralCampaign>
     */
    public function campaignLookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ReferralCampaign>
     */
    public function campaignsByIds(array $ids): Collection;

    public function relationshipOfReferee(int $refereeUserId, ReferralAudience $audience): ?ReferralRelationship;

    public function lockRelationship(int $id): ?ReferralRelationship;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createRelationship(array $attributes): ReferralRelationship;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateRelationship(ReferralRelationship $relationship, array $attributes): ReferralRelationship;

    public function relationshipsOfReferrerSince(int $referrerUserId, ReferralAudience $audience, CarbonInterface $since): int;

    public function relationshipsOfReferrerInCampaign(int $referrerUserId, int $campaignId): int;

    /**
     * Rewards already granted to the referrer in a campaign (the per-referrer cap counts these).
     */
    public function referrerRewardCount(int $referrerUserId, int $campaignId): int;

    /**
     * Qualified relationships still waiting for budget or an active campaign.
     *
     * @return Collection<int, ReferralRelationship>
     */
    public function waitingForReward(int $limit): Collection;

    /**
     * @return Builder<ReferralRelationship>
     */
    public function relationshipsQuery(RelationshipFilterData $filters): Builder;

    /**
     * Relationships of one referrer for the app (newest first).
     *
     * @return Collection<int, ReferralRelationship>
     */
    public function relationshipsOfReferrer(int $referrerUserId, ReferralAudience $audience, int $limit): Collection;

    /**
     * @return array{referred: int, qualified: int, under_review: int}
     */
    public function referrerCounts(int $referrerUserId, ReferralAudience $audience): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createReward(array $attributes): ReferralReward;

    public function findRewardByKey(string $key): ?ReferralReward;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateReward(ReferralReward $reward, array $attributes): ReferralReward;

    /**
     * @return Collection<int, ReferralReward>
     */
    public function rewardsOfUser(int $userId, int $limit): Collection;

    /**
     * @return Builder<ReferralReward>
     */
    public function rewardsQuery(RewardFilterData $filters): Builder;

    /**
     * Distinct customers that hold referral credit whose expiry has passed.
     *
     * @return list<int>
     */
    public function usersWithDueRewards(CarbonInterface $now, int $limit): array;

    /**
     * Relationships that are under review (the risk detector turns them into signals).
     *
     * @return Collection<int, ReferralRelationship>
     */
    public function underReview(): Collection;

    /**
     * Totals per campaign for the admin show page and the report.
     *
     * @return array{relationships: int, qualified: int, rewarded: int, under_review: int, rejected: int, spent_minor: int}
     */
    public function campaignTotals(int $campaignId): array;

    /**
     * @return list<array<string, mixed>> rows of the referral outcomes report
     */
    public function outcomesReport(?CarbonInterface $from, ?CarbonInterface $to): array;
}
