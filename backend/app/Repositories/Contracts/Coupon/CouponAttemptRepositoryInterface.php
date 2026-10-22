<?php

namespace App\Repositories\Contracts\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\CouponAttempt;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Append-only log of coupon attempts (no update or delete).
 *
 * @extends BaseRepositoryInterface<CouponAttempt>
 */
interface CouponAttemptRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function record(array $attributes): CouponAttempt;

    /**
     * @return Builder<CouponAttempt>
     */
    public function datatableQuery(CouponActivityFilterData $filters): Builder;

    /**
     * Account anonymisation: unlinks the customer's attempts and drops the text they typed (a mistyped code can hold anything).
     *
     * @return int attempts scrubbed
     */
    public function anonymiseForUser(int $userId): int;

    /**
     * @return Collection<int, CouponAttempt>
     */
    public function forUser(int $userId, int $limit = 500): Collection;

    /**
     * @return array{total: int, rejected: int}
     */
    public function counts(int $couponId): array;

    /**
     * @return array<string, int> reason code => count of rejected attempts
     */
    public function rejectionsByReason(int $couponId): array;
}
