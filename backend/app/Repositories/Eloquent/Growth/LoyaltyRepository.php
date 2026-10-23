<?php

namespace App\Repositories\Eloquent\Growth;

use App\DTOs\Growth\LoyaltyLevelFilterData;
use App\Enums\Growth\CreditStatus;
use App\Models\LoyaltyAward;
use App\Models\LoyaltyLevel;
use App\Models\LoyaltyMember;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LoyaltyRepository implements LoyaltyRepositoryInterface
{
    public function levels(bool $activeOnly): Collection
    {
        return LoyaltyLevel::query()->when($activeOnly, static fn (Builder $q) => $q->where('is_active', true))->orderBy('min_completed_jobs')->get();
    }

    public function findLevel(int $id): ?LoyaltyLevel
    {
        return LoyaltyLevel::query()->find($id);
    }

    public function lockLevel(int $id): ?LoyaltyLevel
    {
        return LoyaltyLevel::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function createLevel(array $attributes): LoyaltyLevel
    {
        return LoyaltyLevel::query()->create($attributes);
    }

    public function updateLevel(LoyaltyLevel $level, array $attributes): LoyaltyLevel
    {
        $level->forceFill($attributes)->save();

        return $level;
    }

    public function levelWithThresholdExists(int $minCompletedJobs, ?int $exceptId): bool
    {
        return LoyaltyLevel::query()->where('min_completed_jobs', $minCompletedJobs)->when($exceptId !== null, static fn (Builder $q) => $q->whereKeyNot($exceptId))->exists();
    }

    public function levelsQuery(LoyaltyLevelFilterData $filters): Builder
    {
        return LoyaltyLevel::query()->select('loyalty_levels.*')->when($filters->active !== null, static fn (Builder $q) => $q->where('is_active', $filters->active));
    }

    public function memberOf(int $userId): ?LoyaltyMember
    {
        return LoyaltyMember::query()->where('user_id', $userId)->with('level')->first();
    }

    public function lockOrCreateMember(int $userId): LoyaltyMember
    {
        DB::table('loyalty_members')->insertOrIgnore(['user_id' => $userId, 'completed_jobs' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return LoyaltyMember::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    public function updateMember(LoyaltyMember $member, array $attributes): LoyaltyMember
    {
        $member->forceFill($attributes)->save();

        return $member;
    }

    public function awardOf(int $userId, int $levelId): ?LoyaltyAward
    {
        return LoyaltyAward::query()->where('user_id', $userId)->where('level_id', $levelId)->first();
    }

    public function createAward(array $attributes): LoyaltyAward
    {
        return LoyaltyAward::query()->create($attributes);
    }

    public function updateAward(LoyaltyAward $award, array $attributes): LoyaltyAward
    {
        $award->forceFill($attributes)->save();

        return $award;
    }

    public function awardsOfUser(int $userId): Collection
    {
        return LoyaltyAward::query()->where('user_id', $userId)->with('level')->orderBy('granted_at')->get();
    }

    public function usersWithDueAwards(CarbonInterface $now, int $limit): array
    {
        return LoyaltyAward::query()->where('status', CreditStatus::Granted->value)->whereNotNull('expires_at')->where('expires_at', '<=', $now)
            ->distinct()->limit($limit)->pluck('user_id')->map(static fn (mixed $id): int => (int) $id)->all();
    }

    public function membersAtLevel(int $levelId): int
    {
        return LoyaltyMember::query()->where('level_id', $levelId)->count();
    }
}
