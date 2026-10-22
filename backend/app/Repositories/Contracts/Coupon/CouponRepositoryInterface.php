<?php

namespace App\Repositories\Contracts\Coupon;

use App\DTOs\Coupon\CouponFilterData;
use App\Models\Coupon;
use App\Models\CouponVersion;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Coupons, their scope rows and immutable versions. Coupons are archived, never deleted. Counter changes go through
 * `adjustCounters` and are made only while the coupon row is locked.
 *
 * @extends BaseRepositoryInterface<Coupon>
 */
interface CouponRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<Coupon>
     */
    public function datatableQuery(CouponFilterData $filters): Builder;

    public function findByCode(string $code): ?Coupon;

    public function findByUuid(string $uuid): ?Coupon;

    /**
     * Active automatic campaigns (no code) whose window contains `$at`, best priority first, scopes loaded.
     *
     * @return Collection<int, Coupon>
     */
    public function activeAutomatic(CarbonInterface $at): Collection;

    /**
     * Scheduled coupons whose start has come and whose window has not ended.
     *
     * @return Collection<int, Coupon>
     */
    public function dueToStart(CarbonInterface $at): Collection;

    /**
     * @return Collection<int, Coupon>
     */
    public function dueToExpire(CarbonInterface $at): Collection;

    /**
     * Active coupons whose limit or budget is fully consumed by committed uses.
     *
     * @return Collection<int, Coupon>
     */
    public function consumed(): Collection;

    /**
     * Exhausted coupons that got capacity back (a reversal, a raised cap).
     *
     * @return Collection<int, Coupon>
     */
    public function withCapacityAgain(): Collection;

    /**
     * Replaces the scope rows: `[scope type value => list<int> ids]`.
     *
     * @param array<string, list<int>> $scopes
     */
    public function syncScopes(Coupon $coupon, array $scopes): void;

    /**
     * @param array<string, mixed> $attributes
     */
    public function addVersion(Coupon $coupon, array $attributes): CouponVersion;

    public function version(int $versionId): ?CouponVersion;

    public function currentVersion(Coupon $coupon): ?CouponVersion;

    /**
     * Adds signed deltas to the counters of a locked coupon.
     */
    public function adjustCounters(Coupon $coupon, int $usedCount = 0, int $reservedCount = 0, int $spentMinor = 0, int $reservedMinor = 0): void;

    public function codeExists(string $code, ?int $exceptId = null): bool;
}
