<?php

namespace App\Repositories\Contracts\Growth;

use App\DTOs\Growth\LoyaltyLevelFilterData;
use App\Models\LoyaltyAward;
use App\Models\LoyaltyLevel;
use App\Models\LoyaltyMember;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface LoyaltyRepositoryInterface
{
    /**
     * Levels by ascending job threshold.
     *
     * @return Collection<int, LoyaltyLevel>
     */
    public function levels(bool $activeOnly): Collection;

    public function findLevel(int $id): ?LoyaltyLevel;

    public function lockLevel(int $id): ?LoyaltyLevel;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createLevel(array $attributes): LoyaltyLevel;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateLevel(LoyaltyLevel $level, array $attributes): LoyaltyLevel;

    public function levelWithThresholdExists(int $minCompletedJobs, ?int $exceptId): bool;

    /**
     * @return Builder<LoyaltyLevel>
     */
    public function levelsQuery(LoyaltyLevelFilterData $filters): Builder;

    public function memberOf(int $userId): ?LoyaltyMember;

    /**
     * Creates the member row when missing and returns it locked for update.
     */
    public function lockOrCreateMember(int $userId): LoyaltyMember;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateMember(LoyaltyMember $member, array $attributes): LoyaltyMember;

    public function awardOf(int $userId, int $levelId): ?LoyaltyAward;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAward(array $attributes): LoyaltyAward;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateAward(LoyaltyAward $award, array $attributes): LoyaltyAward;

    /**
     * @return Collection<int, LoyaltyAward>
     */
    public function awardsOfUser(int $userId): Collection;

    /**
     * @return list<int>
     */
    public function usersWithDueAwards(CarbonInterface $now, int $limit): array;

    public function membersAtLevel(int $levelId): int;
}
